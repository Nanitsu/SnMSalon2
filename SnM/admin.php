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
$sql = "SELECT uID, username, email, lname, fname, regdate, attempt, status, stats FROM login LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle PDF generation
if (isset($_POST['generate_pdf'])) {
    generatePDF($result);
    exit();
}

function generatePDF($result) {
    require_once('tcpdf/tcpdf.php');

    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('User Report');
    $pdf->SetSubject('User Report');
    $pdf->SetKeywords('TCPDF, PDF, report');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Include Bootstrap CSS
    $html = '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';
    $html .= '<h2>User Report</h2>';
    if (count($result) > 0) {
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Record No</th><th>Name</th><th>Username</th><th>Email</th><th>Registration Date</th><th>Login Attempts</th><th>Status</th><th>Stats</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($result as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['uID']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['lname']) . ' ' . htmlspecialchars($row['fname']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['username']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['email']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['regdate']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['attempt']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['stats']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>No records found.</p>';
    }

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    $pdf->Output('user_report.pdf', 'I');
}

// Handle user update
if (isset($_POST['update'])) {
    $uID = $_POST['uID'];
    $attempt = $_POST['attempt'];
    $status = $_POST['status'];
    $stats = $_POST['stats'];

    $update_sql = "UPDATE login SET attempt = :attempt, status = :status, stats = :stats WHERE uID = :uID";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':attempt', $attempt, PDO::PARAM_INT);
    $update_stmt->bindParam(':status', $status, PDO::PARAM_INT);
    $update_stmt->bindParam(':stats', $stats, PDO::PARAM_STR);
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
    <title>Admin Dashboard</title>
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
        .modal-header, .modal-footer {
            background-color: #f8f9fa;
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
    <a class="navbar-brand" href="#">Users</a>
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
           
            </li>
            <li class="nav-item">
                <a class="nav-link" href="sign.php">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <h2 class="text-center">User List</h2>

    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <?= $_SESSION['message'] ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="text-right mb-3">
        <button type="submit" name="generate_pdf" class="btn btn-primary">Generate PDF</button>
    </form>

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
                        <td>
                            <button class="btn btn-warning btn-sm edit-button" data-toggle="modal" data-target="#editModal" data-id="<?= htmlspecialchars($row['uID']) ?>" data-attempt="<?= htmlspecialchars($row['attempt']) ?>" data-status="<?= htmlspecialchars($row['status']) ?>" data-stats="<?= htmlspecialchars($row['stats']) ?>">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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

<div class="footer">
    <p>&copy; <?= date('Y') ?> Your Company. All rights reserved.</p>
</div>

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
    });
</script>
</body>
</html>
