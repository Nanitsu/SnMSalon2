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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch user information from the database if the user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else {
    // Redirect user to login page if not logged in
    header("Location: sign.php");
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
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

// Fetch total records from the payment table
$total_sql = "SELECT COUNT(*) FROM payment";
$total_stmt = $conn->query($total_sql);
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch payment records
$sql = "SELECT * FROM payment LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Scissors and Mirrors Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        html,
        body {
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
            display: none; /* Initially hide */
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

        .nav-links.show {
            display: flex; /* Show when active */
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

        .logo {
            height: 50px;
            margin-right: 20px;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <div class="app-bar">
        <img src="SnM.jpg" alt="Salon Logo" class="logo">
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Payments - Scissors and Mirrors Salon</div>
        <div class="nav-links" id="nav-links">
            <a href="Audittrail.php">Audit Trail</a>
            <a href="admin.php">Users</a>
            <a href="walkin.php">Walk in Records</a>
            <a href="sign.php">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper container mt-5 mb-5">
        <h2 class="text-center" style="color: #002f6c;">Payments</h2>
        <hr>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>pID</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>User ID</th>
                        <th>Time</th>
                        <th>aID</th>
                        <th>nID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pID']) ?></td>
                        <td><?= htmlspecialchars($row['pAmount']) ?></td>
                        <td><?= htmlspecialchars($row['pType']) ?></td>
                        <td><?= htmlspecialchars($row['uID']) ?></td>
                        <td><?= htmlspecialchars($row['pTime']) ?></td>
                        <td><?= htmlspecialchars($row['aID']) ?></td>
                        <td><?= htmlspecialchars($row['nID']) ?></td>
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
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMenu() {
            const navLinks = document.getElementById('nav-links');
            navLinks.classList.toggle('show'); // Toggle the visibility of the nav links
        }
    </script>
</body>

</html>
