<?php
// Start session
session_start();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password'])) {
    // Connect to the database
    $servername = "localhost";
    $username = "Customer";
    $password = "Garcia@0923";
    $dbname = "sam";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retrieve the email from session
        $email = $_SESSION['reset_email'];

        // Hash the new password securely
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Update the user's password in the database
        $stmt = $conn->prepare("UPDATE login SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);

        // Clear the session
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_verification_code']);

        // Redirect to the login page
        header("Location: sign.php");
        exit();
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
        <h2 class="text-center mb-4">Reset Password</h2>
        <form method="post">
            <div class="form-group">
                <label for="password">Enter your new password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
    </div>
</body>
</html>
