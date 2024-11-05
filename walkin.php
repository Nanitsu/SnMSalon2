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

// Handle form submission for adding and updating records
if (isset($_POST['submit'])) {
    $wName = $_POST['wName'];
    $wService = $_POST['wService'];
    $wColor = $_POST['wColor'];
    $wTime = $_POST['wTime'];
    $wPayment = $_POST['wPayment'];
    
    if (isset($_POST['wID']) && !empty($_POST['wID'])) {
        // Update existing walk-in record
        $wID = $_POST['wID'];
        $update_sql = "UPDATE walkin SET wName = :wName, wService = :wService, wColor = :wColor, wTime = :wTime, wPayment = :wPayment WHERE wID = :wID";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindParam(':wID', $wID);
    } else {
        // Add new walk-in record
        $insert_sql = "INSERT INTO walkin (wName, wService, wColor, wTime, wPayment) VALUES (:wName, :wService, :wColor, :wTime, :wPayment)";
        $update_stmt = $conn->prepare($insert_sql);
    }
    
    $update_stmt->bindParam(':wName', $wName);
    $update_stmt->bindParam(':wService', $wService);
    $update_stmt->bindParam(':wColor', $wColor);
    $update_stmt->bindParam(':wTime', $wTime);
    $update_stmt->bindParam(':wPayment', $wPayment);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = 'Walk-in record saved successfully.';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error saving walk-in record.';
        $_SESSION['msg_type'] = 'danger';
    }
}

// Fetch walk-in records for display
$records_per_page = 20;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

$sql = "SELECT * FROM walkin LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total records for pagination
$total_sql = "SELECT COUNT(*) FROM walkin";
$total_stmt = $conn->query($total_sql);
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Clients - Scissors and Mirrors Salon</title>
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
        <div class="title">Walk-in Clients - Scissors and Mirrors Salon</div>
        <div class="nav-links">
            <a href="Audittrail.php">Audit Trail</a>
            <a href="admin.php">Users</a>
            <a href="ResRec.php">Appointment Records</a>
            <a href="manager.php">Managerial Reports</a>
            <a href="sign.php">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper container mt-5 mb-5">
        <h2 class="text-center" style="color: #002f6c;">Walk-in Clients</h2>
        <hr>
        
        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['message'])): ?>
        <div id="notification" class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
        <?php endif; ?>

        <!-- Form to add or edit walk-in clients -->
        <form method="post" action="">
            <input type="hidden" name="wID" id="wID">
            <div class="form-group">
                <label for="wName">Name</label>
                <input type="text" class="form-control" id="wName" name="wName" required>
            </div>
            <div class="form-group">
                <label for="wService">Service</label>
                <input type="text" class="form-control" id="wService" name="wService" required>
            </div>
            <div class="form-group">
                <label for="wColor">Color</label>
                <input type="text" class="form-control" id="wColor" name="wColor">
            </div>
            <div class="form-group">
                <label for="wTime">Preferred Time</label>
                <select class="form-control" id="wTime" name="wTime" required>
                    <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM</option>
                    <option value="11:00 AM - 01:00 PM">11:00 AM - 01:00 PM</option>
                    <option value="01:00 PM - 03:00 PM">01:00 PM - 03:00 PM</option>
                    <option value="03:00 PM - 05:00 PM">03:00 PM - 05:00 PM</option>
                    <option value="05:00 PM - 07:00 PM">05:00 PM - 07:00 PM</option>
                </select>
            </div>
            <div class="form-group">
                <label for="wPayment">Payment</label>
                <input type="text" class="form-control" id="wPayment" name="wPayment" required>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Save</button>
        </form>

        <!-- Display Walk-in Clients -->
        <div class="table-responsive mt-4">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>wID</th>
                        <th>Name</th>
                        <th>Service</th>
                        <th>Color</th>
                        <th>Time</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['wID']) ?></td>
                        <td><?= htmlspecialchars($row['wName']) ?></td>
                        <td><?= htmlspecialchars($row['wService']) ?></td>
                        <td><?= htmlspecialchars($row['wColor']) ?></td>
                        <td><?= htmlspecialchars($row['wTime']) ?></td>
                        <td><?= htmlspecialchars($row['wPayment']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm edit-button"
                                data-id="<?= htmlspecialchars($row['wID']) ?>"
                                data-name="<?= htmlspecialchars($row['wName']) ?>"
                                data-service="<?= htmlspecialchars($row['wService']) ?>"
                                data-color="<?= htmlspecialchars($row['wColor']) ?>"
                                data-time="<?= htmlspecialchars($row['wTime']) ?>"
                                data-payment="<?= htmlspecialchars($row['wPayment']) ?>">Edit</button>
                        </td>
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

        // Handle the Edit button click
        $('.edit-button').on('click', function() {
            // Fill the form with data from the table row
            $('#wID').val($(this).data('id'));
            $('#wName').val($(this).data('name'));
            $('#wService').val($(this).data('service'));
            $('#wColor').val($(this).data('color'));
            $('#wTime').val($(this).data('time'));
            $('#wPayment').val($(this).data('payment'));
        });

        // Hide notification after 3 seconds
        setTimeout(function() {
            var notification = document.getElementById('notification');
            if (notification) {
                notification.style.display = 'none';
            }
        }, 3000);
    </script>
</body>

</html>
