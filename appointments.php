<?php
session_start();

$servername = "localhost";
$username = "Admin";
$password = "Garcia@1234";
$dbname = "sam";

// Establishing connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch user information from the database if the user is logged in
if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else {
    // Redirect user to login page if not logged in
    header("Location: sign.php");
    exit();
}

// Handle logout
if(isset($_POST['logout'])) {
    // Add logout action to audit trail
    $uID = $_SESSION['uID']; // Extract uID from session
    $action = "logout";
    $audit_sql = "INSERT INTO auditrail (uID, action) VALUES (?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $audit_stmt->execute([$uID, $action]);

    // Clear session variables and redirect to login page
    session_unset();
    session_destroy();
    header("Location: sign.php");
    exit();
}

// Pagination variables
$records_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch total records from appointment and nappointment tables
$total_sql_appointment = "SELECT COUNT(*) FROM appointment";
$total_sql_nappointment = "SELECT COUNT(*) FROM nappointment";
$total_stmt_appointment = $conn->query($total_sql_appointment);
$total_stmt_nappointment = $conn->query($total_sql_nappointment);
$total_records_appointment = $total_stmt_appointment->fetchColumn();
$total_records_nappointment = $total_stmt_nappointment->fetchColumn();
$total_pages = ceil(($total_records_appointment + $total_records_nappointment) / $records_per_page);

// Fetch appointment and nappointment records
$sql_appointment = "SELECT * FROM appointment LIMIT :limit OFFSET :offset";
$sql_nappointment = "SELECT * FROM nappointment LIMIT :limit OFFSET :offset";
$stmt_appointment = $conn->prepare($sql_appointment);
$stmt_nappointment = $conn->prepare($sql_nappointment);
$stmt_appointment->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt_appointment->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_nappointment->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt_nappointment->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_appointment->execute();
$stmt_nappointment->execute();
$result_appointment = $stmt_appointment->fetchAll(PDO::FETCH_ASSOC);
$result_nappointment = $stmt_nappointment->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Scissors and Mirrors Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background-color: #fff;
            color: #333;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1 0 auto;
        }

        .app-bar {
            background-color: #002f6c;
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            color: #fff;
        }

        .title {
            font-size: 32px;
            color: #f0c27b;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
        }

        .burger-menu {
            display: flex;
            flex-direction: column;
            cursor: pointer;
            margin-right: 64%;
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
            left: 20px;
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
            color: #d9a83f;
        }

        .burger-menu.active + .nav-links {
            display: flex;
        }

        .table thead th {
            background-color: #002f6c;
            color: #f0c27b;
            font-family: 'Outfit', sans-serif;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: #e6f0ff;
        }

        .btn-primary {
            background-color: #f0c27b;
            border-color: #f0c27b;
            color: #002f6c;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary:hover {
            background-color: #d9a83f;
            border-color: #d9a83f;
        }

        .modal-header,
        .modal-footer {
            background-color: #f8f9fa;
        }

        .footer {
            background-color: #002f6c;
            color: #fff;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            flex-shrink: 0;
        }

        .footer p {
            margin: 0;
            font-size: 16px;
            color: #f0c27b;
        }

        .footer-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 40px;
            font-size: 18px;
        }

        .footer-links a {
            color: #f0c27b;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #d9a83f;
        }

        .tooltip {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            padding: 5px;
            z-index: 1000;
        }

        /* Added logo styling */
        .logo {
            height: 50px;
            margin-right: 20px;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <div class="app-bar">
        <!-- Adding a logo to the left side -->
        <img src="SnM.jpg" alt="Salon Logo" class="logo">

        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Appointments - Scissors and Mirrors Salon</div>
         <div class="nav-links">
            <a href="profile.php">Profile</a>
            <a href="user.php">User</a>
            <a href="reservation.php">Appointment</a>
            <a href="sign.php">Logout</a>
            <a href="paymentrec.php">Payments</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper container mt-5 mb-5">
        <h2 class="text-center" style="color: #002f6c;">Appointments</h2>
        <hr>
        
        <!-- Display Appointments from the "appointment" table -->
        <h3>Appointments</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>aID</th>
                        <th>Date</th>
                        <th>Service</th>
                        <th>Hair Color</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>User ID</th>
                    
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result_appointment as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['aID']) ?></td>
                        <td><?= htmlspecialchars($row['aDate']) ?></td>
                        <td><?= htmlspecialchars($row['aService']) ?></td>
                        <td><?= htmlspecialchars($row['hairColor']) ?></td>
                        <td><?= htmlspecialchars($row['atimestamp']) ?></td>
                        <td><?= htmlspecialchars($row['Status']) ?></td>
                        <td><?= htmlspecialchars($row['aTime']) ?></td>
                        <td><?= htmlspecialchars($row['uID']) ?></td>
                  
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Display Appointments from the "nappointment" table -->
        <h3>Nail Appointments</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>nID</th>
                        <th>Service</th>
                        <th>Color</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                        <th>User ID</th>
                       
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result_nappointment as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nID']) ?></td>
                        <td><?= htmlspecialchars($row['nService']) ?></td>
                        <td><?= htmlspecialchars($row['nColor']) ?></td>
                        <td><?= htmlspecialchars($row['nTime']) ?></td>
                        <td><?= htmlspecialchars($row['nDate']) ?></td>
                        <td><?= htmlspecialchars($row['ntimestamp']) ?></td>
                        <td><?= htmlspecialchars($row['nstatus']) ?></td>
                        <td><?= htmlspecialchars($row['uID']) ?></td>
                  
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($i == $current_page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> Scissors and Mirrors Salon. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Services</a>
            <a href="#">Contact</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <!-- Bootstrap and jQuery Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-links');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }
    </script>
</body>

</html>
