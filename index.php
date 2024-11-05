<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database connection parameters
$servername = "localhost";
$username = "Customer";
$password = "Garcia@0923";
$dbname = "sam";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

require 'PHPMailer/PHPMailer/vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve user input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $address = trim($_POST['address']);
    $cNum = trim($_POST['cNum']);
    $secret = trim($_POST['secret']);
    $verification_code = $_POST['verification_code'] ?? '';

    if (isset($_POST['send_code'])) {
        // Generate a verification code
        $code = rand(100000, 999999);
        $_SESSION['verification_code'] = $code;
        $_SESSION['user_data'] = [
            'name' => $name,
            'email' => $email,
            'pass' => $pass,
            'fname' => $fname,
            'lname' => $lname,
            'address' => $address,
            'cNum' => $cNum,
            'secret' => $secret
        ];

        // Send the verification email
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
            $mail->SMTPAuth   = true;
            $mail->Username   = 'garcianathaniel923@gmail.com'; // SMTP username
            $mail->Password   = 'zidf loce lpzm uran'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('garcianathaniel923@gmail.com', 'Positif Corp');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification Code';
            $mail->Body    = "Your verification code is $code";

            $mail->send();
            $errorMsg = "Verification code sent. Please check your email.";
        } catch (Exception $e) {
            $errorMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } elseif (isset($_POST['verify_code'])) {
        if ($verification_code == $_SESSION['verification_code']) {
            // Hash the password securely
            $hashed_password = password_hash($_SESSION['user_data']['pass'], PASSWORD_DEFAULT);

            // Insert user data into the database, including the status column set to 1
            $sql = "INSERT INTO login (username, email, password, fname, lname, address, cNum, Secret, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);

            if ($stmt->execute([
                $_SESSION['user_data']['name'],
                $_SESSION['user_data']['email'],
                $hashed_password,
                $_SESSION['user_data']['fname'],
                $_SESSION['user_data']['lname'],
                $_SESSION['user_data']['address'],
                $_SESSION['user_data']['cNum'],
                $_SESSION['user_data']['secret']
            ])) {
                unset($_SESSION['verification_code']);
                unset($_SESSION['user_data']);
                $_SESSION['message'] = "Registered";
                header("Location: sign.php");
                exit();
            } else {
                $errorMsg = "Failed to register. Please try again.";
            }
        } else {
            $errorMsg = "Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <title>User Registration</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: url('salonbg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            display: flex;
            flex-direction: column;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .app-bar {
            background-color: #002f6c;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #f0c27b;
            font-size: 24px;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .navbar-nav .nav-link {
            color: #f0c27b !important;
            font-size: 18px;
            margin: 0 10px;
        }

        .navbar-nav .nav-link:hover {
            color: #d9a83f !important;
        }

        .container {
            max-width: 700px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .form-title {
            text-align: center;
            margin-bottom: 20px;
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: bold;
            color: #002f6c;
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
            height: 60px;
        }

        .form-group label {
            color: #002f6c;
            font-weight: bold;
        }

        .form-control {
            border: 2px solid #e0975f;
            border-radius: 30px;
            padding: 15px;
            width: 100%;
            font-size: 16px;
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

        .form-check-label {
            color: #002f6c;
        }

        .alert {
            text-align: center;
            font-size: 16px;
        }

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            flex-shrink: 0;
        }

        .footer-links a {
            color: #f0c27b;
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #d9a83f;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <div class="app-bar">
        <div class="title">Scissors and Mirrors Salon - User Registration</div>
        <ul class="navbar-nav ml-auto d-flex align-items-center">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
            <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="sign.php">Sign In</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container mt-5">
            <img src="SnM.jpg" alt="Logo" class="logo">
            <h1 class="form-title">Account Creation</h1>
            <form method="post">
                <?php if (!isset($_POST['send_code'])): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Username</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="Username" pattern="[A-Za-z0-9]{4,}" title="Alphanumeric, at least 4 characters" required>
                            </div>
                            <div class="form-group">
                                <label for="pass">Password</label>
                                <input type="password" class="form-control" name="pass" id="pass" placeholder="Password" pattern="(?=.*\d)(?=.*[!@#$%^&*]).{6,}" title="At least 6 characters, one number and one special character" required>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="showPassword" onclick="togglePassword()">
                                <label class="form-check-label" for="showPassword">Show Password</label>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <label for="fname">First Name</label>
                                <input type="text" class="form-control" name="fname" id="fname" placeholder="First Name" pattern="[A-Za-z]{1,50}" title="Letters only, max 50 characters" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lname">Last Name</label>
                                <input type="text" class="form-control" name="lname" id="lname" placeholder="Last Name" pattern="[A-Za-z]{1,50}" title="Letters only, max 50 characters" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" name="address" id="address" placeholder="Address" required>
                            </div>
                            <div class="form-group">
                                <label for="cNum">Contact Number</label>
                                <input type="text" class="form-control" name="cNum" id="cNum" placeholder="Contact Number" pattern="\d{10,15}" title="Valid contact number (10-15 digits)" required>
                            </div>
                            <div class="form-group">
                                <label for="secret">Secret Word or Phrase</label>
                                <input type="text" class="form-control" name="secret" id="secret" placeholder="Secret Word or Phrase" required>
                            </div>
                            <button type="submit" name="send_code" class="btn btn-primary btn-block mt-4">Send Verification Code</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="verification_code">Verification Code</label>
                        <input type="text" class="form-control" name="verification_code" id="verification_code" placeholder="Verification Code" required>
                    </div>
                    <button type="submit" name="verify_code" class="btn btn-primary btn-block mt-4">Verify Code</button>
                <?php endif; ?>
                <p class="text-center mt-3">
                    Already have an account? <a href="sign.php" style="color: #002f6c;">Sign in</a>
                </p>
            </form>
            <?php if (!empty($errorMsg)) : ?>
                <div class="alert alert-danger mt-3"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['message'])) : ?>
                <div class="alert alert-success mt-3"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> Scissors and Mirrors Salon. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Services</a>
            <a href="#">Contact</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passField = document.getElementById('pass');
            var showPassword = document.getElementById('showPassword');
            if (showPassword.checked) {
                passField.type = 'text';
            } else {
                passField.type = 'password';
            }
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
