<?php
session_start();

// Database connection setup
$servername = "localhost";
$dbUsername = "Customer"; // Database username
$dbPassword = "Garcia@0923"; // Database password
$dbname = "sam"; // Database name

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable exceptions for PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize error message
$error_message = '';

// Handle appointment cancellation and time editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_appointment_id'])) {
        $appointment_id = $_POST['cancel_appointment_id'];
        $appointment_type = $_POST['appointment_type'];

        if ($appointment_type == 'appointment') {
            $cancel_sql = "UPDATE appointment SET Status = 'canceled' WHERE aID = ? AND uID = ?";
            $stmt = $conn->prepare($cancel_sql);
            $stmt->execute([$appointment_id, $_SESSION['uID']]);
        } else if ($appointment_type == 'nappointment') {
            $cancel_sql = "UPDATE nappointment SET nstatus = 'canceled' WHERE nID = ? AND uID = ?";
            $stmt = $conn->prepare($cancel_sql);
            $stmt->execute([$appointment_id, $_SESSION['uID']]);
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['edit_appointment_id']) && isset($_POST['new_time'])) {
        $appointment_id = $_POST['edit_appointment_id'];
        $new_time = $_POST['new_time'];
        $appointment_type = $_POST['appointment_type'];

        // Proceed to update the appointment time without time checks
        if ($appointment_type == 'appointment') {
            $edit_sql = "UPDATE appointment SET aTime = ? WHERE aID = ? AND uID = ?";
            $stmt = $conn->prepare($edit_sql);
            $stmt->execute([$new_time, $appointment_id, $_SESSION['uID']]);
        } else if ($appointment_type == 'nappointment') {
            $edit_sql = "UPDATE nappointment SET nTime = ? WHERE nID = ? AND uID = ?";
            $stmt = $conn->prepare($edit_sql);
            $stmt->execute([$new_time, $appointment_id, $_SESSION['uID']]);
        }

        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch user information if the user is logged in
if (isset($_SESSION['username'])) {
    $sessionUsername = $_SESSION['username'];

    $sql = "SELECT uID, username, email, lname, fname, stamps FROM login WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$sessionUsername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $uID = $user['uID'];
        $_SESSION['uID'] = $uID;

        $username = $user['username'];
        $email = $user['email'];
        $lname = $user['lname'];
        $fname = $user['fname'];
        $stamps = $user['stamps'];
    } else {
        echo "User not found.";
        exit();
    }
} else {
    header("Location: sign.php");
    exit();
}

// Fetch appointments for the logged-in user
$all_appointments = [];
if (isset($uID)) {

    // Fetch all details from 'appointment' table for the current user
    $appointment_sql = "SELECT aID, aDate, aTime, aService, hairColor, atimestamp, Status, '' as nPayment, 'appointment' as appointment_type FROM appointment WHERE uID = ? AND Status != 'canceled'";
    $appointment_stmt = $conn->prepare($appointment_sql);
    $appointment_stmt->execute([$uID]);
    $appointments = $appointment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all details from 'nappointment' table for the current user
    $nappointment_sql = "SELECT nID AS aID, nDate AS aDate, nTime AS aTime, nService AS aService, nColor AS hairColor, ntimestamp AS atimestamp, nstatus AS Status, nPayment, 'nappointment' as appointment_type FROM nappointment WHERE uID = ? AND nstatus != 'canceled'";
    $nappointment_stmt = $conn->prepare($nappointment_sql);
    $nappointment_stmt->execute([$uID]);
    $nappointments = $nappointment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Combine both appointment arrays
    $all_appointments = array_merge($appointments, $nappointments);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- (Your existing head content goes here) -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Profile</title>
    <!-- Include Bootstrap CSS and other stylesheets -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <!-- Custom styles -->
    <style>
        body {
            margin: 0;
            padding-top: 80px;
            background: url('profbg.jpg') no-repeat center center fixed;
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
            background-color: #fff;
            margin: 5px 0;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 70px;
            left: 0;
            background-color: #3c3b6e;
            width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: bold;
            text-align: left;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #f0c27b;
        }

        .burger-menu.active+.nav-links {
            display: flex;
        }

        .title {
            font-weight: bold;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
            margin-bottom: 80px;
        }

        .stamp-card {
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .stamps {
            font-size: 30px;
            color: #f0c27b;
        }

        .stamp.filled {
            color: #3c3b6e;
        }

        .reward-message {
            color: green;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #3c3b6e;
            color: #fff;
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
            background-color: #f0c27b;
            color: #3c3b6e;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
        }

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            width: 100%;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            position: fixed;
            bottom: 0;
            left: 0;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .footer-links a {
            color: #002f6c;
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #f0c27b;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        input[type="time"],
        select {
            width: 100px;
        }
    </style>
</head>

<body>
    <div class="app-bar">
        <!-- Your navigation bar content -->
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Scissors and Mirrors Salon - User Profile</div>
        <div class="nav-links">
            <a href="profile.php">Profile</a>
            <a href="user.php">User</a>
            <a href="reservation.php">Appointment</a>
            <a href="sign.php">Logout</a>
        </div>
    </div>

    <div class="container mt-5">
        <h1 class="text-center mt-4">User Profile</h1>
        <div class="card">
            <div class="card-body text-center">
                <h4><?php echo htmlspecialchars($fname . " " . $lname); ?></h4>
                <p>Username: <?php echo htmlspecialchars($username); ?></p>
                <p>Email: <?php echo htmlspecialchars($email); ?></p>
            </div>
        </div>

        <div class="container mt-4">
            <h2>Stamp Card</h2>
            <div class="stamp-card">
                <div class="stamps">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span class="stamp <?= $i <= $stamps ? 'filled' : '' ?>">★</span>
                    <?php endfor; ?>
                </div>
                <?php if ($stamps >= 10): ?>
                    <p class="reward-message">Congratulations! You qualify for a free service!</p>
                <?php else: ?>
                    <p>You need <?= 10 - $stamps ?> more stamps for a reward.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger mt-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="container mt-4">
            <h2>Appointments</h2>
            <?php if (count($all_appointments) > 0): ?>
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Appointment ID</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Service</th>
                            <th>Hair Color</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Edit Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['aID']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['aDate']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['aTime']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['aService']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['hairColor'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($appointment['atimestamp']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['nPayment'] ?? '-'); ?></td>
                                <td>
                                    <!-- Edit Time Form -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="edit_appointment_id" value="<?php echo htmlspecialchars($appointment['aID']); ?>">
                                        <input type="hidden" name="appointment_type" value="<?php echo htmlspecialchars($appointment['appointment_type']); ?>">
                                        <select name="new_time" required>
                                            <option value="">Select Time</option>
                                            <option value="09:00">09:00</option>
                                            <option value="11:00">11:00</option>
                                            <option value="13:00">13:00</option>
                                            <option value="15:00">15:00</option>
                                            <option value="17:00">17:00</option>
                                            <option value="19:00">19:00</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <!-- Cancel Button -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="cancel_appointment_id" value="<?php echo htmlspecialchars($appointment['aID']); ?>">
                                        <input type="hidden" name="appointment_type" value="<?php echo htmlspecialchars($appointment['appointment_type']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</button>
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

    <div class="footer">
        <!-- Your footer content -->
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

    <!-- Include jQuery and other scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Other script content if any -->
</body>

</html>
