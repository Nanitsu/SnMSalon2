<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$servername = "localhost";
$username = "Customer";
$password = "Garcia@0923";
$dbname = "sam";

// Initialize variables
$maxAttempts = 3;

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errorMsg = "";
$lockoutTime = 30; // Lockout time in seconds
$attempt = 0;
$isLocked = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['pass']) && isset($_POST['secret'])) {
    $username = $_POST['username'];
    $pass = $_POST['pass'];
    $secret = $_POST['secret'];

    // Query to find user details from the login table
    $sql = "SELECT * FROM login WHERE LOWER(username) = LOWER(?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $passwordFromDB = $result['password'];
            $secretFromDB = $result['secret'];
            $uID = $result['uID'];
            $status = $result['status'];
            $attempt = isset($result['attempt']) ? $result['attempt'] : 0; // Set to 0 if null

            // If login is successful
            if (password_verify($pass, $passwordFromDB) && $secret === $secretFromDB) {
                $_SESSION['username'] = $username;
                $_SESSION['uID'] = $uID;

                // Reset attempt counter after successful login
                $updateAttemptSql = "UPDATE login SET attempt = 0 WHERE uID = ?";
                $updateAttemptStmt = $conn->prepare($updateAttemptSql);
                $updateAttemptStmt->execute([$uID]);

                // Log the login action in the audit trail
                $action = "Logged in";
                $auditSql = "INSERT INTO auditrail (uID, Action) VALUES (?, ?)";
                $auditStmt = $conn->prepare($auditSql);
                $auditStmt->execute([$uID, $action]);

                // Redirect user based on their status
                if ($status == 1) {
                    header("Location: user.php");
                } elseif ($status == 2) {
                    header("Location: admin.php");
                } else {
                    $errorMsg = "Unknown user status.";
                }
                exit();
            } else {
                // Increment attempt counter on failed login
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    // Display error if attempts exceed maxAttempts
                    $isLocked = true;
                    $_SESSION['lock_time'] = time(); // Record the lockout time
                } else {
                    $errorMsg = "Invalid password, username, or secret word!";
                }

                // Update attempt counter
                $updateAttemptSql = "UPDATE login SET attempt = ? WHERE uID = ?";
                $updateAttemptStmt = $conn->prepare($updateAttemptSql);
                $updateAttemptStmt->execute([$attempt, $uID]);
            }
        } else {
            $errorMsg = "Invalid Username or Password!";
        }
    } else {
        $errorMsg = "Error in SQL query preparation: " . $conn->errorInfo()[2];
    }
}

// Handle unlocking after 30 seconds
if (isset($_SESSION['lock_time'])) {
    $timeSinceLock = time() - $_SESSION['lock_time'];
    if ($timeSinceLock >= $lockoutTime) {
        // Reset the attempt counter and unlock the form
        $updateAttemptSql = "UPDATE login SET attempt = 0 WHERE LOWER(username) = LOWER(?)";
        $updateAttemptStmt = $conn->prepare($updateAttemptSql);
        $updateAttemptStmt->execute([$_POST['username']]);
        unset($_SESSION['lock_time']);
        $isLocked = false;
    } else {
        $isLocked = true;
        $remainingTime = $lockoutTime - $timeSinceLock;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Positif Salon Login</title>
    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #e1e5ea;
            font-family: 'Outfit', sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 500px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 80px;
            border-radius: 50%;
        }
        h2 {
            color: #002f6c;
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group label {
            color: #002f6c;
            font-weight: bold;
        }
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 8px;
        }
        .form-control:focus {
            border-color: #f0c27b;
            box-shadow: 0 0 0 0.2rem rgba(240, 194, 123, 0.25);
        }
        .btn-primary {
            background-color: #f0c27b;
            color: #002f6c;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            padding: 12px;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #d9a83f;
        }
        .text-center a {
            color: #002f6c;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="SnM.jpg" alt="Logo" class="logo">
        <h2>Welcome to Scissors and Mirrors Salon</h2>
        <form method="post" id="loginForm">
            <?php if (!empty($errorMsg)) : ?>
                <div class="error-message"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" pattern="[A-Za-z0-9]{4,}" title="Username must be alphanumeric and at least 4 characters long" required <?php if ($isLocked) echo 'disabled'; ?>>
                <small class="form-text">Alphanumeric, at least 4 characters</small>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="pass" placeholder="Enter password" pattern="(?=.*\d)(?=.*[!@#$%^&*]).{6,}" title="Password must be at least 6 characters long and contain at least one number and one special character (!@#$%^&*)" required <?php if ($isLocked) echo 'disabled'; ?>>
                <small class="form-text">At least 6 characters, with a number and special character</small>
                <input type="checkbox" id="showPassword"> Show Password
            </div>
            <div class="form-group">
                <label for="secret">Secret Word or Phrase:</label>
                <input type="password" class="form-control" id="secret" name="secret" placeholder="Enter secret word or phrase" pattern="[A-Za-z0-9\s]{4,}" title="Secret must be at least 4 characters long" required <?php if ($isLocked) echo 'disabled'; ?>>
                <small class="form-text">At least 4 characters</small>
                <input type="checkbox" id="showSecret"> Show Secret
            </div>
            <button type="submit" class="btn btn-primary" <?php if ($isLocked) echo 'disabled'; ?>>Login</button>
            <p class="text-center">
                Not registered yet? <a href="index.php">Register here</a>
            </p>
            <p class="text-center">
                Forgot password? <a href="forgot_password.php">Click here</a>
            </p>
            <?php if ($isLocked) : ?>
                <p class="text-center text-danger" id="lockoutMessage">Please wait <span id="countdown"><?php echo $remainingTime; ?></span> seconds before trying again.</p>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('showPassword').addEventListener('change', function() {
            var passwordField = document.getElementById('password');
            passwordField.type = this.checked ? 'text' : 'password';
        });

        // Toggle secret visibility
        document.getElementById('showSecret').addEventListener('change', function() {
            var secretField = document.getElementById('secret');
            secretField.type = this.checked ? 'text' : 'password';
        });

        <?php if ($isLocked) : ?>
        var countdown = document.getElementById('countdown');
        var timeLeft = <?php echo $remainingTime; ?>;

        var timer = setInterval(function() {
            timeLeft--;
            countdown.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                // Remove the countdown and lockout message
                document.getElementById('lockoutMessage').style.display = 'none';
                // Enable the form fields
                document.getElementById('username').disabled = false;
                document.getElementById('password').disabled = false;
                document.getElementById('secret').disabled = false;
                document.querySelector('.btn-primary').disabled = false;
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>
