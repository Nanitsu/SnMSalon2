<?php
// Enable full error reporting and display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Database connection settings
$servername = "localhost";
$username = "Customer";
$password = "Garcia@0923";
$dbname = "sam";

// Initialize variables
$maxAttempts = 3;
$lockoutTime = 30; // Lockout time in seconds
$errorMsg = "";
$attempt = 0;
$isLocked = false;

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['pass'])) {
    $username = $_POST['username'];
    $pass = $_POST['pass'];

    // Query to find user details from the login table
    $sql = "SELECT * FROM login WHERE LOWER(username) = LOWER(?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Retrieve values from the result set
            $passwordFromDB = $result['password'];
            $uID = $result['uID'];
            $status = $result['status'];
            $attempt = isset($result['attempt']) ? $result['attempt'] : 0;

            // Check if login credentials are correct
            if (password_verify($pass, $passwordFromDB)) {
                // Set session variables
                $_SESSION['username'] = $username;
                $_SESSION['uID'] = $uID;

                // Reset login attempt counter
                $updateAttemptSql = "UPDATE login SET attempt = 0 WHERE uID = ?";
                $updateAttemptStmt = $conn->prepare($updateAttemptSql);
                $updateAttemptStmt->execute([$uID]);

                // Log the login action in the audit trail
                $action = "Logged in";
                $auditSql = "INSERT INTO auditrail (uID, Action) VALUES (?, ?)";
                $auditStmt = $conn->prepare($auditSql);
                $auditStmt->execute([$uID, $action]);

                // Close session writing to ensure it's saved before redirection
                session_write_close();

                // Redirect based on user status
                if ($status == 1) {
                    header("Location: user.php");
                } elseif ($status == 2) {
                    header("Location: Admindb.php");
                } else {
                    $errorMsg = "Unknown user status.";
                }
                exit();
            } else {
                // Handle failed login attempt
                $attempt++;
                if ($attempt >= $maxAttempts) {
                    // Lock the user out
                    $isLocked = true;
                    $_SESSION['lock_time'] = time(); // Record the lockout time
                    $errorMsg = "Account locked due to too many failed login attempts. Please wait for $lockoutTime seconds.";
                } else {
                    $errorMsg = "Invalid username or password.";
                }

                // Update login attempt count
                $updateAttemptSql = "UPDATE login SET attempt = ? WHERE uID = ?";
                $updateAttemptStmt = $conn->prepare($updateAttemptSql);
                $updateAttemptStmt->execute([$attempt, $uID]);
            }
        } else {
            $errorMsg = "Invalid Username or Password!";
        }
    } else {
        $errorMsg = "Error in SQL query preparation.";
    }
}

// Handle unlocking after the lockout period
if (isset($_SESSION['lock_time'])) {
    $timeSinceLock = time() - $_SESSION['lock_time'];
    if ($timeSinceLock >= $lockoutTime) {
        // Unlock the user by resetting the attempt counter
        if (isset($_POST['username'])) {
            $updateAttemptSql = "UPDATE login SET attempt = 0 WHERE LOWER(username) = LOWER(?)";
            $updateAttemptStmt = $conn->prepare($updateAttemptSql);
            $updateAttemptStmt->execute([$_POST['username']]);
        }
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
    <title>Scissors and Mirrors Salon Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('salonbg.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Outfit', sans-serif;
            flex-direction: column;
        }

        .app-bar {
            background-color: #002f6c;
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #f0c27b;
            font-size: 24px;
            font-family: 'Playfair Display', serif;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 10;
        }

        .title {
            font-weight: bold;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            padding:40px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
            margin-top: 0%; 
            margin-bottom: px; /* To adjust for the fixed footer */
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 100px;
            border-radius: 50%;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        h2 {
            color: #e0975f;
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            color: #002f6c;
            font-weight: bold;
        }

        .form-control {
            border: 2px solid #e0975f;
            border-radius: 30px;
            padding: 15px;
            width: 75%;
            font-size: 16px;
            color: #333;
            background: #fff;
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #f0c27b;
            box-shadow: 0 0 10px rgba(240, 194, 123, 0.5);
            outline: none;
        }

        .btn-primary {
            background-color: #002f6c;
            color: #f0c27b;
            border: none;
            border-radius: 30px;
            font-weight: bold;
            padding: 15px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:hover {
            background-color: #e0975f;
            color: #002f6c;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
        }

        .text-center a {
            color: #e0975f;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .text-center a:hover {
            text-decoration: underline;
            color: #d18449;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .countdown {
            color: #d9534f;
            font-weight: bold;
        }

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            width: 100%;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            position: fixed;
            bottom: 0;
            left: 0;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .footer-links a {
            color: #f0c27b;
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #e0975f;
        }
    </style>
</head>

<body>
    <div class="app-bar">
        <div class="title">Scissors and Mirrors Salon</div>
    </div>

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
            <button type="submit" class="btn btn-primary" <?php if ($isLocked) echo 'disabled'; ?>>Login</button>
            <p class="text-center">
                Not registered yet? <a href="index.php">Register here</a>
            </p>
            <p class="text-center">
                Forgot password? <a href="forgot_password.php">Click here</a>
            </p>
            <?php if ($isLocked) : ?>
                <p class="text-center" id="lockoutMessage">Please wait <span id="countdown" class="countdown"><?php echo $remainingTime; ?></span> seconds before trying again.</p>
            <?php endif; ?>
        </form>
    </div>

    <div class="footer">
        <p>&copy; 2024 Scissors and Mirrors Salon. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Services</a>
            <a href="#">Contact</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('showPassword').addEventListener('change', function() {
            var passwordField = document.getElementById('password');
            passwordField.type = this.checked ? 'text' : 'password';
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
                document.querySelector('.btn-primary').disabled = false;
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>

</html>
