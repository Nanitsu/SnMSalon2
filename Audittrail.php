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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Scissors and Mirrors Salon</title>
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
            margin-right: 65%    ;
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
        <div class="title">Audit Trail - Scissors and Mirrors Salon</div>
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
        <div class="logo-container text-center mb-4">
            <img src="SnM.jpg" alt="Logo" style="width: 100px; height: auto;">
        </div>
        <h2 class="text-center" style="color: #002f6c;">Audit Trail</h2>
        <hr>
        <form method="post" class="form-inline mb-3">
            <label for="uID_filter" class="mr-2">Filter by User ID:</label>
            <input type="number" id="uID_filter" name="uID_filter" value="<?php echo htmlspecialchars($uID_filter); ?>" class="form-control mr-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <form method="post" class="mb-3">
            <button type="submit" name="generate_pdf" class="btn btn-primary">Generate PDF</button>
        </form>

        <?php if (count($result) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Audit ID</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                            <th>User ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['AuditID']) ?></td>
                            <td><?= htmlspecialchars($row['Action']) ?></td>
                            <td><?= htmlspecialchars($row['AuditTime']) ?></td>
                            <td onmouseover="showTooltip(event, '<?= htmlspecialchars($row['fname']) ?>', '<?= htmlspecialchars($row['lname']) ?>')" onmouseout="hideTooltip()">
                                <?= htmlspecialchars($row['uID']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No results found</p>
        <?php endif; ?>

        <div id="tooltip" class="tooltip"></div>

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
</body>

</html>
