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

        if ($verification_code == $_SESSION['reset_verification_code']) {
            // Redirect to reset password page or show password reset form
            header("Location: reset_password.php");
            exit();
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-image: url('background.jpg'); /* Replace 'background.jpg' with your background image path */
            background-size: cover;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            width: 400px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="card">
        <h2 class="text-center mb-4">Forgot Password</h2>
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
                    <label for="verification_code">Enter Verification Code</label>
                    <input required type="text" class="form-control" name="verification_code" id="verification_code" placeholder="Verification Code">
                </div>
                <button type="submit" name="verify_code" class="btn btn-primary btn-block">Verify Code</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
