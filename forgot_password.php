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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

require 'PHPMailer/PHPMailer/vendor/autoload.php'; // Load Composer's autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['send_code'])) {
        // Retrieve user input
        $email = trim($_POST['email']);
        
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT * FROM login WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a verification code
            $code = rand(100000, 999999);
            $_SESSION['reset_verification_code'] = $code;
            $_SESSION['reset_email'] = $email;

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
                $mail->setFrom('garcianathaniel923@gmail.com', 'Positif');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Verification Code';
                $mail->Body    = "Your verification code is $code";

                $mail->send();
                $errorMsg = "Verification code sent. Please check your email.";
            } catch (Exception $e) {
                $errorMsg = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $errorMsg = "Email not found.";
        }
    } elseif (isset($_POST['verify_code'])) {
        $verification_code = $_POST['verification_code'] ?? '';
        $secret = $_POST['secret'] ?? '';

        // Retrieve the stored secret from the database for the entered email
        $email = $_SESSION['reset_email'] ?? '';
        $stmt = $conn->prepare("SELECT secret FROM login WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($verification_code == $_SESSION['reset_verification_code'] && $secret == $user['secret']) {
            // Redirect to reset password page or show password reset form
            header("Location: reset_password.php");
            exit();
        } else {
            $errorMsg = "Invalid verification code or secret phrase.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        }
        .card {
            width: 100%;
            max-width: 400px; /* Max width to limit the text box length */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: rgba(255, 255, 255, 0.9);
            text-align: center;
            backdrop-filter: blur(10px);
        }
        h2 {
            color: #e0975f;
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            color: #002f6c;
            font-weight: bold;
        }
        .form-control {
            border: 2px solid #e0975f;
            border-radius: 30px;
            padding: 5px;  /* Reduced padding to make text boxes shorter */
            width: 50%;
            font-size: 14px;
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
            padding: 10px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }
        .btn-primary:hover {
            background-color: #e0975f;
            color: #002f6c;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
        }
        .alert {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Forgot Password</h2>
        <?php if (!empty($errorMsg)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <?php if (!isset($_POST['send_code'])): ?>
                <div class="form-group">
                    <label for="email">Enter your email address:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" name="send_code" class="btn btn-primary btn-block">Send Verification Code</button>
            <?php else: ?>
                <div class="form-group">
                    <label for="verification_code">Enter Verification Code:</label>
                    <input type="text" class="form-control" name="verification_code" id="verification_code" placeholder="Verification Code" required>
                </div>
                <div class="form-group">
                    <label for="secret">Enter Secret Phrase:</label>
                    <input type="password" class="form-control" name="secret" id="secret" placeholder="Secret Phrase" required>
                </div>
                <button type="submit" name="verify_code" class="btn btn-primary btn-block">Verify Code</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
