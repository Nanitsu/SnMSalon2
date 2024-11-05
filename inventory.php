<?php
session_start();

$servername = "localhost";
$username = "Admin";
$dbpassword = "Garcia@1234";
$dbname = "sam";

// Establishing connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $dbpassword);
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

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action == 'insert') {
            $name = $_POST['pName'];
            $price = $_POST['pPrice'];
            $stock = $_POST['pStock'];
            $image = $_POST['image'];

            $insert_sql = "INSERT INTO product (pName, pPrice, pStock, image) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->execute([$name, $price, $stock, $image]);
            echo json_encode(['message' => 'Product added successfully']);
            exit();
        } elseif ($action == 'update') {
            $id = $_POST['prodID'];
            $name = $_POST['pName'];
            $price = $_POST['pPrice'];
            $stock = $_POST['pStock'];
            $image = $_POST['image'];

            $update_sql = "UPDATE product SET pName = ?, pPrice = ?, pStock = ?, image = ? WHERE prodID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->execute([$name, $price, $stock, $image, $id]);
            echo json_encode(['message' => 'Product updated successfully']);
            exit();
        } elseif ($action == 'delete') {
            $id = $_POST['prodID'];

            $delete_sql = "DELETE FROM product WHERE prodID = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Product deleted successfully']);
            exit();
        }
    }
}

// Retrieve products from the database
$products = [];
$product_sql = "SELECT prodID, pName, pPrice, pStock, image FROM product";
foreach ($conn->query($product_sql) as $row) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product Management</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .table-container {
            margin-top: 20px;
        }
        .item-img {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Inventory</a>
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
                    <a class="nav-link" href="sign.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container">
        <h2 class="mt-4 mb-3">Product List</h2>
        <button class="btn btn-primary mb-3" onclick="showInsertForm()">Add New Product</button>
        <div class="table-responsive table-container">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTable">
                    <?php foreach ($products as $product): ?>
                        <tr id="row-<?php echo $product['prodID']; ?>">
                            <td><?php echo $product['prodID']; ?></td>
                            <td><?php echo htmlspecialchars($product['pName']); ?></td>
                            <td><?php echo htmlspecialchars($product['pPrice']); ?></td>
                            <td><?php echo htmlspecialchars($product['pStock']); ?></td>
                            <td><img src="<?php echo htmlspecialchars($product['image']); ?>" class="item-img"></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="showEditForm(<?php echo $product['prodID']; ?>)">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteProduct(<?php echo $product['prodID']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Insert/Edit -->
    <div class="modal fade" id="productModal" tabindex="-1" role="dialog" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="prodID" name="prodID">
                        <div class="form-group">
                            <label for="pName">Name</label>
                            <input type="text" class="form-control" id="pName" name="pName" required>
                        </div>
                        <div class="form-group">
                            <label for="pPrice">Price</label>
                            <input type="number" class="form-control" id="pPrice" name="pPrice" required>
                        </div>
                        <div class="form-group">
                            <label for="pStock">Stock</label>
                            <input type="number" class="form-control" id="pStock" name="pStock" required>
                        </div>
                        <div class="form-group">
                            <label for="image">Image URL</label>
                            <input type="text" class="form-control" id="image" name="image" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Show Insert Form
        function showInsertForm() {
            $('#prodID').val('');
            $('#pName').val('');
            $('#pPrice').val('');
            $('#pStock').val('');
            $('#image').val('');
            $('#productModalLabel').text('Add New Product');
            $('#productModal').modal('show');
        }

        // Show Edit Form
        function showEditForm(id) {
            const row = document.getElementById('row-' + id);
            const cells = row.getElementsByTagName('td');

            $('#prodID').val(id);
            $('#pName').val(cells[1].innerText);
            $('#pPrice').val(cells[2].innerText);
            $('#pStock').val(cells[3].innerText);
            $('#image').val(cells[4].getElementsByTagName('img')[0].src);
            $('#productModalLabel').text('Edit Product');
            $('#productModal').modal('show');
        }

        // Handle Form Submit
        $('#productForm').submit(function(event) {
            event.preventDefault();

            const formData = $(this).serialize();
            const action = $('#prodID').val() ? 'update' : 'insert';

            $.post('', formData + '&action=' + action, function(response) {
                const res = JSON.parse(response);
                alert(res.message);
                location.reload();
            });
        });

        // Delete Product
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                $.post('', { action: 'delete', prodID: id }, function(response) {
                    const res = JSON.parse(response);
                    alert(res.message);
                    location.reload();
                });
            }
        }
    </script>
</body>
</html>
