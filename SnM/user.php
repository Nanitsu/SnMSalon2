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

$sql = "SELECT username, email, lname, fname, address, cNum, img FROM login WHERE uID = ?";
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
    
    if (isset($_FILES['fileImg']['tmp_name']) && !empty($_FILES['fileImg']['tmp_name'])) {
        $imgData = file_get_contents($_FILES['fileImg']['tmp_name']);
        $update_img_sql = "UPDATE login SET img = ? WHERE uID = ?";
        $update_img_stmt = $conn->prepare($update_img_sql);
        $update_img_stmt->execute([$imgData, $uID]);
    }
    
    $updateSuccess = true;
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
     <style>
        body {
            background: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex-grow: 1; /* Allows the container to grow and push the footer down */
            margin-top: 20px;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        footer {
            background-color: #f3e5ab !important;
            color: black;
            padding: 20px 0;
            text-align: center;
            margin-top: auto; /* Ensures the footer stays at the bottom */
        }

        .navbar-light .navbar-nav .nav-link {
            color: rgba(0, 0, 0, .5);
        }

        .navbar-light .navbar-nav .nav-link:hover {
            color: rgba(0, 0, 0, .9);
        }

        .bg-cream {
            background-color: #f3e5ab !important;
        }

        
    </style>
</head>
<body>
   <nav class="navbar navbar-expand-lg navbar-light bg-cream">
        <a class="navbar-brand" href="#"><img src="no.jpg" alt="Logo" class="logo"></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">User</a></li>
               
              
                <li class="nav-item"><a class="nav-link" href="Reservation.php">Appointment</a></li>
            
              <li class="nav-item"><a class="nav-link" href="sign.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center mt-4">User Profile</h1>
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group text-center">
                                <?php
                                if (isset($result['img']) && !empty($result['img'])) {
                                    $imageSrc = 'data:image/jpeg;base64,' . base64_encode($result['img']);
                                    echo "<img id='previewImg' src='$imageSrc' alt='Profile Picture' class='img-fluid rounded-circle profile-img'>";
                                } else {
                                    echo "<img id='previewImg' src='noprofile.png' alt='Profile Picture' class='img-fluid rounded-circle profile-img'>";
                                }
                                ?>
                                <div class="upload-btn mt-3">
                                    <input type="file" name="fileImg" id="fileImg" accept=".jpg, .jpeg, .png" onchange="previewImage(event)">
                                </div>
                            </div>
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

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('previewImg');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
    <footer>
        <p>&copy; 2024 Positif. All rights reserved.</p>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
