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

// Handle uID filter
$uID_filter = isset($_POST['uID_filter']) ? intval($_POST['uID_filter']) : '';

// Pagination variables
$records_per_page = 30;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch the total number of records for the filtered uID
$total_sql = "SELECT COUNT(*) FROM auditrail";
if ($uID_filter) {
    $total_sql .= " WHERE uID = :uID_filter";
}
$total_stmt = $conn->prepare($total_sql);
if ($uID_filter) {
    $total_stmt->bindParam(':uID_filter', $uID_filter, PDO::PARAM_INT);
}
$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page and filtered uID
$sql = "SELECT AuditID, Action, AuditTime, auditrail.uID, login.fname, login.lname 
        FROM auditrail 
        JOIN login ON auditrail.uID = login.uID";
if ($uID_filter) {
    $sql .= " WHERE auditrail.uID = :uID_filter";
}
$sql .= " ORDER BY AuditTime DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
if ($uID_filter) {
    $stmt->bindParam(':uID_filter', $uID_filter, PDO::PARAM_INT);
}
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PDF generation function
function generatePDF($result) {
    require_once('tcpdf/tcpdf.php');
    
    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Audit Trail Report');
    $pdf->SetSubject('Audit Trail Report');
    $pdf->SetKeywords('TCPDF, PDF, audit trail');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Add logo and date
    $logo_path = K_PATH_IMAGES . 'positif.jpg'; // Ensure this path is correct and image exists
    $pdf->Image($logo_path, 10, 10, 30, '', 'JPG');
    $pdf->SetXY(50, 10);
    $pdf->Write(0, '    Generated on: ' . date('Y-m-d'));

    // Generate HTML content for the PDF
    $html = '<h2>Audit Trail Report</h2>';
    $html .= '<table border="1">';
    $html .= '<tr><th>Audit ID</th><th>Action</th><th>Timestamp</th><th>User ID</th></tr>';
    foreach ($result as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['AuditID']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['Action']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['AuditTime']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['uID']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    $pdf->Output('audit_trail_report.pdf', 'I');
}

// PDF generation request
if (isset($_POST['generate_pdf'])) {
    generatePDF($result);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit Trail</title>
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
        .tooltip {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            padding: 5px;
            z-index: 1000;
        }
        .logo-container {
            text-align: center;
            margin: 20px 0;
        }
    </style>
    <script>
        function showTooltip(event, fName, lName) {
            var tooltip = document.getElementById('tooltip');
            tooltip.innerHTML = fName + ' ' + lName;
            tooltip.style.top = event.clientY + 5 + 'px';
            tooltip.style.left = event.clientX + 5 + 'px';
            tooltip.style.display = 'block';
        }

        function hideTooltip() {
            var tooltip = document.getElementById('tooltip');
            tooltip.style.display = 'none';
        }
    </script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Audit Trail</a>
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
         
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ResRec.php">Appointment Records</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manager.php">Managerial Reports</a>
            </li>
  
            </li>
             <li class="nav-item">
                <a class="nav-link" href="sign.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="logo-container">
        <img src="positif.jpg" alt="Logo" style="width: 100px; height: auto;">
    </div>
    <h1>Audit Trail</h1>
    <hr>
    <form method="post" class="form-inline mb-3">
        <label for="uID_filter" class="mr-2">Filter by User ID:</label>
        <input type="number" id="uID_filter" name="uID_filter" value="<?php echo htmlspecialchars($uID_filter); ?>" class="form-control mr-2">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <form method="post" class="mb-3">
        <button type="submit" name="generate_pdf" class="btn btn-success">Generate PDF</button>
    </form>

    <?php
    if (count($result) > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Audit ID</th><th>Action</th><th>Timestamp</th><th>User ID</th></thead><tbody>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row["AuditID"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["Action"]) . "</td>";
            echo "<td>" . htmlspecialchars($row["AuditTime"]) . "</td>";
            echo "<td onmouseover='showTooltip(event, \"" . htmlspecialchars($row['fname']) . "\", \"" . htmlspecialchars($row['lname']) . "\")' onmouseout='hideTooltip()'>" . htmlspecialchars($row["uID"]) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No results found</p>";
    }
    ?>

    <div id="tooltip" class="tooltip"></div>

    <div class="pagination">
        <?php
        for ($i = 1; $i <= $total_pages; $i++) {
            $active_class = $i == $current_page ? 'active' : '';
            echo "<a class='page-link $active_class' href='?page=$i'>$i</a>";
        }
        ?>
    </div>
</div>

<div class="footer">
    &copy; 2024 Positif. All rights reserved.
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
