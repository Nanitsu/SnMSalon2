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
    $html .= '<tr><th>Appointment ID</th><th>Customer Name</th><th>Appointment Date</th><th>Service</th><th>Nail Color</th><th>Hair Color</th><th>Time</th><th>Payment</th><th>Status</th><th>Action</th></tr>';
    foreach ($result as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['aID']) . '</td>';
        $html .= '<td>' . $row['fName'] . ' ' . $row['lName'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aDate']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aService']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['nailColor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['hairColor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['aTime']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['payment']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Status']) . '</td>';
        $html .= '<td><button class="btn btn-primary edit-btn" data-reserve-id="' . htmlspecialchars($row['aID']) . '" data-status="' . htmlspecialchars($row['Status']) . '" data-toggle="modal" data-target="#editModal">Edit</button></td>';
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
    <title>Appointment Records</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            font-size: 1.1rem;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background-color: #343a40;
            color: white;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
        .logo-container {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Appointment Report</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="Audittrail.php">Audit Trail</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="admin.php">Users</a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="ResRec.php">Appointment Records</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manager.php">Managerial Reports</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="login.php">Profile</a>
            </li>
            <li class="nav-item">
                <form method="POST" action="">
                    <button type="submit" name="logout" class="btn btn-danger">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <div class="logo-container">
        <img src="SnM.jpg" alt="Logo" style="width: 150px;">
    </div>
    <h2>Appointment Records</h2>
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
        <?php
        if ($result && count($result) > 0) {
            echo "<table class='table table-striped table-bordered'>";
            echo "<thead class='thead-dark'><tr><th>Appointment ID</th><th>First Name</th><th>Last Name</th><th>Appointment Date</th><th>Service</th><th>Nail Color</th><th>Hair Color</th><th>Time</th><th>Payment</th><th>Status</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['aID']) . "</td>";
                echo "<td>" . htmlspecialchars($row['fName']) . "</td>";
                echo "<td>" . htmlspecialchars($row['lName']) . "</td>";
                echo "<td>" . htmlspecialchars($row['aDate']) . "</td>";
                echo "<td>" . htmlspecialchars($row['aService']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nailColor']) . "</td>";
                echo "<td>" . htmlspecialchars($row['hairColor']) . "</td>";
                echo "<td>" . htmlspecialchars($row['aTime']) . "</td>";
                echo "<td>" . htmlspecialchars($row['payment']) . "</td>";
                echo "<td class='status-cell' data-reserve-id='" . htmlspecialchars($row['aID']) . "'>" . htmlspecialchars($row['Status']) . "</td>";
                echo "<td><button class='btn btn-primary edit-btn' data-reserve-id='" . htmlspecialchars($row['aID']) . "' data-status='" . htmlspecialchars($row['Status']) . "' data-toggle='modal' data-target='#editModal'>Edit</button></td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No records found.</p>";
        }
        ?>
    </div>

    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=1&month=<?php echo $month; ?>">First</a>
            </li>
            <li class="page-item <?php echo ($current_page == 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&month=<?php echo $month; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&month=<?php echo $month; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&month=<?php echo $month; ?>">Next</a>
            </li>
            <li class="page-item <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $total_pages; ?>&month=<?php echo $month; ?>">Last</a>
            </li>
        </ul>
    </nav>
</div>

<div class="footer">
    <p>&copy; 2024 Your Company Name. All Rights Reserved.</p>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
