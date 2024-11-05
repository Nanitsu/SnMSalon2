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
    session_unset();
    session_destroy();
    header("Location: sign.php");
    exit();
}

// Fetch product data
$sql = "SELECT prodID, pName, pPrice, pStock, image FROM product";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle PDF generation
if (isset($_POST['generate_pdf'])) {
    generatePDF($products);
    exit();
}

function generatePDF($products) {
    require_once('tcpdf/tcpdf.php');
    
    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Product List');
    $pdf->SetSubject('Product List');
    $pdf->SetKeywords('TCPDF, PDF, product, list');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 10);

    // Add logo and date
    $logo_path = K_PATH_IMAGES . 'positif.jpg'; // Update the logo path as needed
    $pdf->Image($logo_path, 10, 10, 30, '', 'JPG');
    $pdf->SetXY(50, 10);
    $pdf->Write(0, '    Generated on: ' . date('Y-m-d'));

    // Generate HTML content
    $html = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">';
    $html .= '<h2>Product List</h2>';
    if (count($products) > 0) {
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Product ID</th><th>Product Name</th><th>Price</th><th>Stock</th><th>Image</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($products as $product) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($product['prodID']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['pName']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['pPrice']) . '</td>';
            $html .= '<td>' . htmlspecialchars($product['pStock']) . '</td>';
            $html .= '<td><img src="' . htmlspecialchars($product['image']) . '" width="50" height="50"></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>No products found.</p>';
    }

    // Write HTML content to PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Output PDF
    $pdf->Output('product_list.pdf', 'I');
}

// Handle product addition
if (isset($_POST['add_product'])) {
    $pName = $_POST['pName'];
    $pPrice = $_POST['pPrice'];
    $pStock = $_POST['pStock'];
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $image = $targetFile;
        }
    }

    $insert_sql = "INSERT INTO product (pName, pPrice, pStock, image) VALUES (:pName, :pPrice, :pStock, :image)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bindParam(':pName', $pName);
    $insert_stmt->bindParam(':pPrice', $pPrice);
    $insert_stmt->bindParam(':pStock', $pStock);
    $insert_stmt->bindParam(':image', $image);
    $insert_stmt->execute();

    // Refresh the page to show the new product
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle product update
if (isset($_POST['update_product'])) {
    $prodID = $_POST['prodID'];
    $pName = $_POST['pName'];
    $pPrice = $_POST['pPrice'];
    $pStock = $_POST['pStock'];
    $image = $_POST['existing_image'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $image = $targetFile;
        }
    }

    $update_sql = "UPDATE product SET pName = :pName, pPrice = :pPrice, pStock = :pStock, image = :image WHERE prodID = :prodID";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bindParam(':prodID', $prodID);
    $update_stmt->bindParam(':pName', $pName);
    $update_stmt->bindParam(':pPrice', $pPrice);
    $update_stmt->bindParam(':pStock', $pStock);
    $update_stmt->bindParam(':image', $image);
    $update_stmt->execute();

    // Refresh the page to show the updated product
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Product Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .container {
            margin-top: 2rem;
        }
        table img {
            max-width: 100%;
            height: auto;
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
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Stock Report</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
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
                    <a class="nav-link" href="sign.php">Logout Reports</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5 mb-5">
    <div class="logo-container">
        <img src="positif.jpg" alt="Logo" style="width: 100px; height: auto;">
    </div>
    <h1>Stock Reports</h1>

    <form method="post" class="mb-3">
        <button type="submit" name="generate_pdf" class="btn btn-success">Generate PDF</button>
    </form>

    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">Add New Product</button>

    <?php if (count($products) > 0): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['prodID']) ?></td>
                        <td><?= htmlspecialchars($product['pName']) ?></td>
                        <td><?= htmlspecialchars($product['pPrice']) ?></td>
                        <td><?= htmlspecialchars($product['pStock']) ?></td>
                        <td><img src="<?= htmlspecialchars($product['image']) ?>" width="50" height="50"></td>
                        <td>
                            <button class="btn btn-warning" onclick="editProduct(<?= $product['prodID'] ?>, '<?= htmlspecialchars(addslashes($product['pName'])) ?>', <?= $product['pPrice'] ?>, <?= $product['pStock'] ?>, '<?= htmlspecialchars(addslashes($product['image'])) ?>')">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_pName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="add_pName" name="pName" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_pPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="add_pPrice" name="pPrice" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_pStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="add_pStock" name="pStock" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="add_image" name="image" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="edit_prodID" name="prodID">
                    <div class="mb-3">
                        <label for="edit_pName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="edit_pName" name="pName" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_pPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="edit_pPrice" name="pPrice" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_pStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="edit_pStock" name="pStock" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="edit_image" name="image">
                        <input type="hidden" id="existing_image" name="existing_image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_product" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="footer">
    &copy; 2024 Positif. All rights reserved.
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function editProduct(prodID, pName, pPrice, pStock, image) {
    document.getElementById('edit_prodID').value = prodID;
    document.getElementById('edit_pName').value = pName;
    document.getElementById('edit_pPrice').value = pPrice;
    document.getElementById('edit_pStock').value = pStock;
    document.getElementById('existing_image').value = image;
    var editProductModal = new bootstrap.Modal(document.getElementById('editProductModal'));
    editProductModal.show();
}
</script>
</body>
</html>
