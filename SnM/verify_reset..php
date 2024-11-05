<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code']) && isset($_POST['new_password'])) {
    $entered_code = $_POST['verification_code'];
    $stored_code = $_SESSION['reset_verification_code'];

    if ($entered_code == $stored_code) {
        // Verification code matches, update password in the database
        $email = $_SESSION['reset_email'];
        $new_password = $_POST['new_password'];

        // Implement code to update the password in the database

        // Clear session data
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_verification_code']);

        // Redirect to login page after password reset
        header("Location: sign.php");
        exit();
    } else {
        $errorMsg = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Password Reset</title>
    <style>
        body {
            background-image: url('signbg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 400px;
            margin-top: 100px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px auto;
        }
    </style>
</head>
<body>
    <img src="positif.jpg" alt="Logo" class="logo" style="height: 60px;">
    <div class="container">
        <h2 class="text-center">Reset Password</h2>
        <form method="post">
            <?php if (!empty($errorMsg)) : ?>
                <div class="alert alert-danger" role="alert"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="verification_code">Enter the verification code sent to your email:</label>
                <input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="Verification Code" required>
            </div>
            <div class="form-group">
                <label for="new_password">Enter your new password:</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
