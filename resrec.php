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
    $uID = $_SESSION['uID']; // Extract uID from session
} else {
    // Redirect user to login page if not logged in
    header("Location: sign.php");
    exit();
}

// Handle logout
if(isset($_POST['logout'])) {
    // Add logout action to audit trail
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
$total_records_sql = "SELECT COUNT(*) FROM appointment";
$total_records_stmt = $conn->prepare($total_records_sql);
$total_records_stmt->execute();
$total_records = $total_records_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$sql = "SELECT appointment.aID, appointment.aDate, appointment.aService, appointment.nailColor, 
               appointment.hairColor, appointment.timestamp, appointment.Status, appointment.aTime, 
               appointment.uID, appointment.payment, login.fName, login.lName
        FROM appointment
        JOIN login ON appointment.uID = login.uID
        WHERE DATE_FORMAT(appointment.aDate, '%Y-%m') = :month
        LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':month', $month, PDO::PARAM_STR);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle PDF generation
if (isset($_POST['generate_pdf'])) {
    generatePDF($result, $month);
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_id']) && isset($_POST['status'])) {
    $reserveID = $_POST['reserve_id'];
    $newStatus = $_POST['status'];

    // Prepare SQL to update status
    $update_sql = "UPDATE appointment SET Status = :status WHERE aID = :reserve_id";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
    $update_stmt->bindParam(':reserve_id', $reserveID, PDO::PARAM_INT);
    
    // Execute SQL update
    if ($update_stmt->execute()) {
        // Redirect to avoid resubmission
        header("Location: ResRec.php?page=$current_page&month=$month");
        exit();
    } else {
        echo "Error updating status.";
    }
}

function generatePDF($result, $month) {
    require_once('tcpdf/tcpdf.php');
    
    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Appointment Report');
    $pdf->SetSubject('Appointment Report');
    $pdf->SetKeywords('TCPDF, PDF, report');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Add logo and date
    $logo_path = K_PATH_IMAGES . 'SnM.jpg'; // Update the logo path as needed
    $pdf->Image($logo_path, 10, 10, 30, '', 'JPG');
    $pdf->SetXY(50, 10);
    $pdf->Write(0, '    Generated on: ' . date('Y-m-d'));

    // Generate HTML content
    $html = '<h2>Appointment Report for ' . date('F Y', strtotime($month)) . '</h2>';
    $html .= '<table border="1">';
    $html .= '<tr><th>Appointment ID</th><th>Customer Name</th><th>Appointment Date</th><th>Service</th><th>Nail Color</th><th>Hair Color</th><th>Time</th><th>Payment</th><th>Status</th></tr>';
    foreach ($result as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['aID']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['fName']) . ' ' . htmlspecialchars($row['lName']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aDate']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aService']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nailColor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['hairColor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aTime']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['payment']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Status']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    $pdf->Output('appointment_report.pdf', 'I');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Records - Scissors and Mirrors Salon</title>
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
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 32px;
            color: #f0c27b;
            font-family: 'Playfair Display', serif;
            font-weight: bold;
        }

        .navbar-nav .nav-link {
            color: #f0c27b !important;
            font-size: 18px;
            font-family: 'Outfit', sans-serif;
            margin: 0 10px;
        }

        .navbar-nav .nav-link:hover {
            color: #d9a83f !important;
        }

        .table thead th {
            background-color: #002f6c;
            color: #f0c27b;
            font-family: 'Outfit', sans-serif;
        }

        .table tbody tr:nth-of-type(odd) {
            background-color: #e6f0ff;
        }

        .table tbody tr:nth-of-type(even) {
            background-color: #f9f9f9;
        }

        .btn-primary,
        .btn-success {
            background-color: #f0c27b;
            border-color: #f0c27b;
            color: #002f6c;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary:hover,
        .btn-success:hover {
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
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <div class="app-bar">
        <div class="title">Appointment Records - Scissors and Mirrors Salon</div>
        <ul class="navbar-nav ml-auto d-flex align-items-center">
            <li class="nav-item"><a class="nav-link" href="Audittrail.php">Audit Trail</a></li>
            <li class="nav-item"><a class="nav-link" href="admin.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="ResRec.php">Appointment Records</a></li>
            <li class="nav-item"><a class="nav-link" href="manager.php">Managerial Reports</a></li>
            <li class="nav-item">
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-danger ml-2">Logout</button>
                </form>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper container mt-5 mb-5">
        <div class="logo-container text-center mb-4">
            <img src="SnM.jpg" alt="Logo" style="width: 150px;">
        </div>
        <h2 class="text-center" style="color: #002f6c;">Appointment Records</h2>
        <h5>Selected Month: <?php echo date('F Y', strtotime($month)); ?></h5>
        
        <!-- Filter by month -->
        <form method="GET" class="mb-3">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="month">Select Month</label>
                    <input type="month" name="month" id="month" class="form-control" value="<?php echo $month; ?>">
                </div>
                <div class="form-group col-md-6 align-self-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <button type="submit" name="generate_pdf" class="btn btn-success">Generate PDF</button>
                </div>
            </div>
        </form>
        
        <!-- Appointment Records Table -->
        <div class="table-responsive">
            <?php if ($result && count($result) > 0): ?>
                <table class='table table-striped table-bordered'>
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Appointment Date</th>
                            <th>Service</th>
                            <th>Nail Color</th>
                            <th>Hair Color</th>
                            <th>Time</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['aID']) ?></td>
                                <td><?= htmlspecialchars($row['fName']) ?></td>
                                <td><?= htmlspecialchars($row['lName']) ?></td>
                                <td><?= htmlspecialchars($row['aDate']) ?></td>
                                <td><?= htmlspecialchars($row['aService']) ?></td>
                                <td><?= htmlspecialchars($row['nailColor']) ?></td>
                                <td><?= htmlspecialchars($row['hairColor']) ?></td>
                                <td><?= htmlspecialchars($row['aTime']) ?></td>
                                <td><?= htmlspecialchars($row['payment']) ?></td>
                                <td class='status-cell' data-reserve-id='<?= htmlspecialchars($row['aID']) ?>'><?= htmlspecialchars($row['Status']) ?></td>
                                <td><button class='btn btn-primary edit-btn' data-reserve-id='<?= htmlspecialchars($row['aID']) ?>' data-status='<?= htmlspecialchars($row['Status']) ?>' data-toggle='modal' data-target='#editModal'>Edit</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No records found.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($current_page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1&month=<?= $month ?>">First</a>
                </li>
                <li class="page-item <?= ($current_page == 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>&month=<?= $month ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($current_page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&month=<?= $month ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($current_page == $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>&month=<?= $month ?>">Next</a>
                </li>
                <li class="page-item <?= ($current_page == $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $total_pages ?>&month=<?= $month ?>">Last</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Scissors and Mirrors Salon. All Rights Reserved.</p>
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
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
