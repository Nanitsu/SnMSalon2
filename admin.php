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
$records_per_page = 30;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch total records for pagination
$total_records_sql = "SELECT COUNT(*) FROM login";
$total_records_stmt = $conn->prepare($total_records_sql);
$total_records_stmt->execute();
$total_records = $total_records_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch all records without date filtering
$sql = "SELECT uID, username, email, lname, fname, regdate, attempt, status, stats, stamps FROM login LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user update
if (isset($_POST['update'])) {
    $uID = $_POST['uID'];
    $attempt = $_POST['attempt'];
    $status = $_POST['status'];
    $stats = $_POST['stats'];
    $stamps = $_POST['stamps'];

    $update_sql = "UPDATE login SET attempt = :attempt, status = :status, stats = :stats, stamps = :stamps WHERE uID = :uID";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
    $update_stmt->bindParam(':status', $status, PDO::PARAM_INT);
    $update_stmt->bindParam(':stats', $stats, PDO::PARAM_STR);
    $update_stmt->bindParam(':stamps', $stamps, PDO::PARAM_INT);
    $update_stmt->bindParam(':uID', $uID, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        // Record the action in the audit trail
        $action = "edit user";
        $audit_sql = "INSERT INTO auditrail (uID, action) VALUES (?, ?)";
        $audit_stmt = $conn->prepare($audit_sql);
        $audit_stmt->execute([$uID, $action]);

        // Set success message
        $_SESSION['message'] = "User updated successfully.";
        $_SESSION['msg_type'] = "success";

        // Redirect to the same page to reflect the changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Scissors and Mirrors Salon</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
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
            font-size: 24px;
            color: #f0c27b;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
        }

        .burger-menu {
            display: flex;
            flex-direction: column;
            cursor: pointer;
            margin-right: 20px;
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

        .btn-primary,
        .btn-warning {
            background-color: #f0c27b;
            border-color: #f0c27b;
            color: #002f6c;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary:hover,
        .btn-warning:hover {
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

        /* Adjust title to align center when burger menu is on the left */
        .app-bar-content {
            display: flex;
            align-items: center;
            flex: 1;
            justify-content: center;
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
        <div class="app-bar-content">
            <div class="title">Admin Dashboard - Scissors and Mirrors Salon</div>
        </div>
        <div style="width: 50px;"></div>
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
        <h2 class="text-center mb-4" style="color: #002f6c;">User Management</h2>

        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
        <?php endif; ?>

        <!-- User Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Record No</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registration Date</th>
                        <th>Login Attempts</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th>Stamps</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['uID']) ?></td>
                        <td><?= htmlspecialchars($row['lname']) . ' ' . htmlspecialchars($row['fname']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['regdate']) ?></td>
                        <td><?= htmlspecialchars($row['attempt']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['stats']) ?></td>
                        <td><?= htmlspecialchars($row['stamps']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-button" data-toggle="modal" data-target="#editModal"
                                data-id="<?= htmlspecialchars($row['uID']) ?>" data-attempt="<?= htmlspecialchars($row['attempt']) ?>"
                                data-status="<?= htmlspecialchars($row['status']) ?>" data-stats="<?= htmlspecialchars($row['stats']) ?>"
                                data-stamps="<?= htmlspecialchars($row['stamps']) ?>">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($current_page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($current_page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($current_page == $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>

        <!-- Edit User Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="uID" id="modal_uID" value="">
                            <div class="form-group">
                                <label for="attempt">Login Attempts</label>
                                <input type="number" class="form-control" name="attempt" id="modal_attempt" required>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <input type="number" class="form-control" name="status" id="modal_status" required>
                            </div>
                            <div class="form-group">
                                <label for="stats">Stats</label>
                                <input type="text" class="form-control" name="stats" id="modal_stats" required>
                            </div>
                            <div class="form-group">
                                <label for="stamps">Stamps</label>
                                <input type="number" class="form-control" name="stamps" id="modal_stamps" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" name="update" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Handle the Edit button click
        $('.edit-button').on('click', function() {
            var modal = $('#editModal');
            modal.find('#modal_uID').val($(this).data('id'));
            modal.find('#modal_attempt').val($(this).data('attempt'));
            modal.find('#modal_status').val($(this).data('status'));
            modal.find('#modal_stats').val($(this).data('stats'));
            modal.find('#modal_stamps').val($(this).data('stamps'));
        });

        function toggleMenu() {
            const menu = document.querySelector('.nav-links');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }
    </script>
</body>

</html>
