<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['verification_code'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "Customer";
$password = "Garcia@0923";
$dbname = "sam";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code'])) {
    $user_code = $_POST['verification_code'];
    $session_code = $_SESSION['verification_code'];

    if ($user_code == $session_code) {
        $uID = $_SESSION['uID'];
        $action = "login";
        $audit_sql = "INSERT INTO auditrail (uID, action) VALUES (?, ?)";
        $audit_stmt = $conn->prepare($audit_sql);
        $audit_stmt->execute([$uID, $action]);

        unset($_SESSION['verification_code']);

        $sql = "SELECT status FROM login WHERE uID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$uID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $status = $result['status'];

        if ($status == 2) {
            header("Location: admin.php");
        } elseif ($status == 1) {
            header("Location: user.php");
        } else {
            $errorMsg = "Unexpected user status!";
        }
        exit();
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
    <title>Email Verification</title>
    <style>
        body {
            background-image: url('verifybg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            height: 100vh;
            margin: 0;
        }
        .verification-container {
            max-width: 400px;
            margin-top: 50px;
            margin: 150px auto;
            padding: 20px;
            background: linear-gradient(to right, #ff7e5f, #feb47b);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .logo {
            display: block;
            margin: 0 auto 100px auto;
        }
    </style>
</head>
<body>
    <img src="positif.jpg" alt="Logo" class="logo" style="height: 60px;">
    <div class="container verification-container">
        <h2 class="text-center">Email Verification</h2>
        <form method="post">
            <?php if (!empty($errorMsg)) : ?>
                <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="verification_code">Verification Code:</label>
                <input type="text" class="form-control" id="verification_code" name="verification_code" placeholder="Enter verification code" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
