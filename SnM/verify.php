<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_data'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code'])) {
    $entered_code = $_POST['verification_code'];
    if ($entered_code == $_SESSION['user_data']['verification_code']) {
        // User data from session
        $name = $_SESSION['user_data']['name'];
        $email = $_SESSION['user_data']['email'];
        $hashed_password = $_SESSION['user_data']['password'];
        $fname = $_SESSION['user_data']['fname'];
        $lname = $_SESSION['user_data']['lname'];
        $address = $_SESSION['user_data']['address'];
        $cNum = $_SESSION['user_data']['cNum'];
        $status = 1;

        // Database connection parameters
        $servername = "localhost";
        $username = "Customer";
        $password = "Garcia@0923";
        $dbname = "sam";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert user data into the database
            $stmt = $conn->prepare("INSERT INTO login (username, email, password, fname, lname, address, cNum, status) VALUES (:name, :email, :password, :fname, :lname, :address, :cNum, :status)");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':cNum', $cNum);
            $stmt->bindParam(':status', $status);
            if ($stmt->execute()) {
                error_log("User data inserted successfully: $name");
            } else {
                error_log("User data insertion failed: " . implode(", ", $stmt->errorInfo()));
            }

            // Clear the session data
            unset($_SESSION['user_data']);

            // Redirect to login page
            header("Location: sign.php");
            exit();
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
        }
    } else {
        $errorMsg = "Invalid verification code!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Verify Account</title>
    <style>
        body {
            background-image: url('verifybg.jpg'); /* Path to your background image */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 400px;
            margin-top: 10px;
            padding: 20px;
            background: linear-gradient(to right, #ff7e5f, #feb47b); /* Gradient background */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: absolute;
            right: 50px; /* Adjust this value as needed */
            top: 50%;
            transform: translateY(-50%);
        }
        .form-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="positif.jpg" alt="Logo" class="logo" style="height: 60px;">
        <h1 class="form-title">Verify Your Account</h1>
        <form method="post">
            <div class="form-group">
                <label for="verification_code">Enter verification code</label>
                <input required type="text" class="form-control" name="verification_code" id="verification_code" placeholder="Verification Code">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify</button>
            <?php if (!empty($errorMsg)) : ?>
                <div class="alert alert-danger mt-3"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
