<?php
session_start();

// Database connection setup
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

// Fetch user information if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    $sql = "SELECT username, email, lname, fname, img FROM login WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username]);
    $stmt->bindColumn('username', $username);
    $stmt->bindColumn('email', $email);
    $stmt->bindColumn('lname', $lname);
    $stmt->bindColumn('fname', $fname);
    $stmt->bindColumn('img', $imageData, PDO::PARAM_LOB);
    $stmt->fetch(PDO::FETCH_BOUND);

    // Convert LOB to string
    if (is_resource($imageData)) {
        $imageData = stream_get_contents($imageData);
    }
} else {
    header("Location: sign.php");
    exit();
}

// Fetch user ID
if (isset($_SESSION['uID'])) {
    $uID = $_SESSION['uID'];

    // Fetch appointments for the current user
    $appointment_sql = "SELECT * FROM appointment WHERE uID = ? AND Status = 'active'";
    $appointment_stmt = $conn->prepare($appointment_sql);
    $appointment_stmt->execute([$uID]);
    $appointments = $appointment_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $appointments = [];
}

// Handle appointment time update
if (isset($_POST['update_time']) && isset($uID)) {
    $appointment_id = $_POST['appointment_id'];
    $new_time = $_POST['new_time'];

    // Update the appointment time in the database
    $update_sql = "UPDATE appointment SET aTime = ? WHERE aID = ? AND uID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->execute([$new_time, $appointment_id, $uID]);

    // Log the action in the audit trail
    $action = "Updated appointment time: $appointment_id to $new_time";
    $audit_sql = "INSERT INTO auditrail (uID, action) VALUES (?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $audit_stmt->execute([$uID, $action]);
    
    // Optionally, you can refresh the page after updating
    header("Location: profile.php");
    exit();
}

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
        }
         }
         .navbar-light .navbar-nav .nav-link {
            color: rgba(0,0,0,.5);
        }
        .navbar-light .navbar-nav .nav-link:hover {
            color: rgba(0,0,0,.9);
        }
        .bg-cream {
            background-color: #f3e5ab !important; /* Cream color */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-cream">
        <a class="navbar-brand" href="#"><img src="positif.jpg" alt="Logo" class="logo"></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <?php
                        if (isset($imageData) && !empty($imageData)) {
                            $imageSrc = 'data:image/jpeg;base64,' . base64_encode($imageData);
                            echo "<img src='$imageSrc' alt='Profile Picture' class='profile-img'>";
                        } else {
                            echo "<img src='noprofile.png' alt='Profile Picture' class='profile-img'>";
                        }
                        ?>
                        <h4><?php echo htmlspecialchars($fname . " " . $lname); ?></h4>
                        <p><?php echo htmlspecialchars($username); ?></p>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <h2>Appointments</h2>
            <?php if (count($appointments) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Appointment ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['aID']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['aDate']); ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['aID']); ?>">
                                        <input type="time" name="new_time" value="<?php echo htmlspecialchars($appointment['aTime']); ?>" required>
                                        <button type="submit" class="btn btn-primary" name="update_time">Update</button>
                                    </form>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['aService']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['aID']); ?>">
                                        <button type="submit" class="btn btn-danger" name="cancel_appointment">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No active appointments.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2023 Scissors and Mirrors. All Rights Reserved.</p>
    </footer>

</body>
</html>
