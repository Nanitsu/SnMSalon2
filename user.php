<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['uID'])) {
    header("Location: sign.php");
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
$updateSuccess = false;
$uID = $_SESSION['uID'];

$sql = "SELECT username, email, lname, fname, address, cNum FROM login WHERE uID = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$uID]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $errorMsg = "User not found or database error.";
}

if (isset($_POST['submit'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $address = $_POST['address'];
    $cNum = $_POST['cNum'];
    
    $update_sql = "UPDATE login SET fname = ?, lname = ?, address = ?, cNum = ? WHERE uID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$fname, $lname, $address, $cNum, $uID]);
    
    }

if (isset($_POST['logout'])) {
    $uID = $_SESSION['uID'];
    $action = "logout";
    $audit_sql = "INSERT INTO auditrail (uID, action) VALUES (?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $audit_stmt->execute([$uID, $action]);

    session_unset();
    session_destroy();
    header("Location: sign.php");
    exit();
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Profile</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: url('USERBG.JPG') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Outfit', sans-serif;
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

        .burger-menu {
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        .burger-menu div {
            width: 30px;
            height: 4px;
            background-color: #f0c27b;
            margin: 5px 0;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 70px;
            left: 0;
            background-color: #002f6c;
            width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-links a {
            color: #f0c27b;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: bold;
            text-align: left;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #e0975f;
        }

        .burger-menu.active + .nav-links {
            display: flex;
        }

        .title {
            font-weight: bold;
        }

        .container {
            width: 100%;
            max-width: 700px;
            padding: 40px;
            padding-top: 10%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
            margin-top: %; /* To adjust for the fixed header */
     /* To adjust for the fixed footer */
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

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            width: 100%;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
          
            bottom: 0;
            left: 0;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
             flex-shrink: 0;
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
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Scissors and Mirrors Salon - User Profile</div>
        <div class="nav-links">
            <a href="reservation.php">Appointment</a>
            <a href="profile.php">Profile</a>
            <a href="user.php">User</a>
            <a href="sign.php">Logout</a>
        </div>
    </div>

    <div class="container mt-5">
        <h1 class="text-center mt-4">User Profile</h1>
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($result['username']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($result['email']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="fname">First Name</label>
                                <input type="text" class="form-control" id="fname" name="fname" value="<?php echo htmlspecialchars($result['fname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lname">Last Name</label>
                                <input type="text" class="form-control" id="lname" name="lname" value="<?php echo htmlspecialchars($result['lname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($result['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="cNum">Contact Number</label>
                                <input type="text" class="form-control" id="cNum" name="cNum" value="<?php echo htmlspecialchars($result['cNum']); ?>" required>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary btn-block">Update Profile</button>
                        </form>
                        <?php if ($updateSuccess): ?>
                            <div class="alert alert-success mt-3">Profile updated successfully!</div>
                        <?php elseif ($errorMsg): ?>
                            <div class="alert alert-danger mt-3"><?php echo $errorMsg; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
        function toggleMenu() {
            const menu = document.querySelector('.nav-links');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
