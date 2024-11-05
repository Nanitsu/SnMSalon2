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

// Handle oName filter
$oName = isset($_POST['oName']) ? $_POST['oName'] : '';

// Fetch distinct oNames for dropdown
$oNames_sql = "SELECT DISTINCT oName FROM orderlist";
$oNames_stmt = $conn->prepare($oNames_sql);
$oNames_stmt->execute();
$oNames = $oNames_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination variables
$records_per_page = 30;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch the total number of records and total amount
if ($oName) {
    $total_sql = "SELECT COUNT(*), SUM(p.pAmount) FROM orderlist o JOIN payment p ON o.oid = p.oid WHERE o.oName = :oName";
    $total_stmt = $conn->prepare($total_sql);
    $total_stmt->bindParam(':oName', $oName, PDO::PARAM_STR);
} else {
    $total_sql = "SELECT COUNT(*), SUM(p.pAmount) FROM orderlist o JOIN payment p ON o.oid = p.oid";
    $total_stmt = $conn->prepare($total_sql);
}
$total_stmt->execute();
$total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
$total_records = $total_result['COUNT(*)'];
$total_amount = $total_result['SUM(p.pAmount)'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records for the current page
if ($oName) {
    $sql = "SELECT o.oid, l.fName, l.lName, o.uID, o.oTime, p.pTime, p.pAmount, o.oName, o.oQuan 
            FROM orderlist o 
            JOIN login l ON o.uID = l.uID 
            JOIN payment p ON o.oid = p.oid 
            WHERE o.oName = :oName 
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':oName', $oName, PDO::PARAM_STR);
} else {
    $sql = "SELECT o.oid, l.fName, l.lName, o.uID, o.oTime, p.pTime, p.pAmount, o.oName, o.oQuan 
            FROM orderlist o 
            JOIN login l ON o.uID = l.uID 
            JOIN payment p ON o.oid = p.oid 
            LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging output
    if (empty($result)) {
        echo "<p>No records found.</p>";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Handle PDF generation
if (isset($_POST['generate_pdf'])) {
    generatePDF($result, $oName, $total_amount);
    exit();
}

function generatePDF($result, $oName, $total_amount) {
    require_once('tcpdf/tcpdf.php');
    
    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Order and Payment Report');
    $pdf->SetSubject('Order and Payment Report');
    $pdf->SetKeywords('TCPDF, PDF, report');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Add logo and date
    $logo_path = 'positif.jpg'; // Update the logo path as needed
    $pdf->Image($logo_path, 10, 10, 30, '', 'JPG');
    $pdf->SetXY(50, 10);
    $pdf->Write(0, '    Generated on: ' . date('Y-m-d'));

    // Generate HTML content for the PDF
    $html = '<h2>Sales Report</h2>';
    if ($oName) {
        $html .= '<h3>for Order Name: ' . htmlspecialchars($oName) . '</h3>';
    }
    $html .= '<h4>Total Amount: ₱ ' . htmlspecialchars($total_amount) . '</h4>';
    $html .= '<table border="1">';
    $html .= '<tr><th>Order ID</th><th>Customer Name</th><th>Order Date</th><th>Payment Date</th><th>Amount</th><th>Order Name</th><th>Order Quantity</th></tr>';
    foreach ($result as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['oid']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['fName']) . ' ' . htmlspecialchars($row['lName']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['oTime']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['pTime']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['pAmount']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['oName']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['oQuan']) . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    $pdf->Output('sales_report.pdf', 'I');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order and Payment Records</title>
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
    <a class="navbar-brand" href="#">Sales Report</a>
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
                <a class="nav-link" href="sales.php">Sales</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="ResRec.php">Appointment Records</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manager.php">Managerial Reports</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="stocks.php">Stock Reports</a>
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
    <h1>Sales Report</h1>
    <hr>
    <form method="post" class="form-inline mb-3">
        <label for="oName" class="mr-2">Order Name:</label>
        <select id="oName" name="oName" class="form-control mr-2">
            <option value="">Select Order Name</option>
            <?php
            foreach ($oNames as $name) {
                echo '<option value="' . htmlspecialchars($name['oName']) . '">' . htmlspecialchars($name['oName']) . '</option>';
            }
            ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="submit" name="generate_pdf" class="btn btn-secondary ml-2">Print PDF</button>
    </form>

    <?php
    if ($total_amount !== null) {
        echo "<h4>Total Amount: ₱ $total_amount</h4>";
    }

    if (count($result) > 0) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Order ID</th><th>Customer Name</th><th>Order Date</th><th>Payment Date</th><th>Amount</th><th>Order Name</th><th>Order Quantity</th></tr></thead><tbody>";
        
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['oid']) . "</td>";
            echo "<td>" . htmlspecialchars($row['fName']) . " " . htmlspecialchars($row['lName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['oTime']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pTime']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pAmount']) . "</td>";
            echo "<td>" . htmlspecialchars($row['oName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['oQuan']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        // Pagination links
        echo "<div class='pagination'>";
        if ($current_page > 1) {
            echo "<a class='page-link' href='?page=" . ($current_page - 1) . "'>&laquo; Previous</a>";
        }
        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<a class='page-link " . ($i == $current_page ? "active" : "") . "' href='?page=$i'>$i</a>";
        }
        if ($current_page < $total_pages) {
            echo "<a class='page-link' href='?page=" . ($current_page + 1) . "'>Next &raquo;</a>";
        }
        echo "</div>";
    } else {
        echo "<p>No records found.</p>";
    }
    ?>
</div>

<div id="tooltip" class="tooltip"></div>

<div class="footer">
    &copy; 2024 Positif. All rights reserved.
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
