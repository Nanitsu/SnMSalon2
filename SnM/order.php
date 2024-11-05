<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$servername = "localhost";
$username = "Customer";
$password = "Garcia@0923";
$dbname = "sam";

try {
    // Create a PDO connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to fetch product data
    $sql = "SELECT prodID, pName, pPrice, pStock FROM product";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Fetch data and populate the products array
    $products = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $products[] = [
            "id" => $row["prodID"],
            "name" => $row["pName"],
            "price" => $row["pPrice"],
            "stock" => $row["pStock"]
        ];
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Mapping of product IDs to image URLs
$productImages = [
    1 => 'Galbar.jpg',
    2 => 'cement.jpg',
    3 => 'hblock.jpg',
    4 => 'hud.jpg',
    5 => 'cad.jpg'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Ordering Page</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
          .navbar-brand img {
            height: 60px;
        }
        .product-frame {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin: 10px;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s;
        }
        .product-frame:hover {
            transform: translateY(-5px);
        }
        .product-image {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .button {
            background-color: #007bff;
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 5px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .alert {
            display: none;
        }
        footer {
            background-color: #f3e5ab !important;
            color: black;
            padding: 20px 0;
            text-align: center;
            margin-top: auto;
        }
   .navbar-light .navbar-nav .nav-link {
            color: rgba(0,0,0,.5);
        }
        .navbar-light .navbar-nav .nav-link:hover {
            color: rgba(0,0,0,.9);
        }
        .bg-cream {
            background-color: #f3e5ab !important; /* Cream color */
        }

    </style>
</head>
<body>
   <nav class="navbar navbar-expand-lg navbar-light bg-cream">
        <a class="navbar-brand" href="#"><img src="positif.jpg" alt="Logo" class="logo"></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">User</a></li>
                <li class="nav-item"><a class="nav-link" href="order.php">Order</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                <li class="nav-item"><a class="nav-link" href="Reservation.php">Appointment</a></li>
                <li class="nav-item"><a class="nav-link" href="positif.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="sign.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="alert alert-success" id="success-alert">
            Added to cart successfully!
        </div>
        <div class="row">
            <?php if (isset($products) && is_array($products) && count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card product-frame">
                            <img src="<?php echo htmlspecialchars($productImages[$product['id']] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img-top product-image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text">Price: ₱<?php echo htmlspecialchars($product['price']); ?></p>
                                <p class="card-text">Stock: <?php echo htmlspecialchars($product['stock']); ?></p>
                                <form method="post" action="cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($product['price']); ?>">
                                    <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($productImages[$product['id']] ?? 'default.jpg'); ?>">
                                    <input type="hidden" name="product_stock" value="<?php echo htmlspecialchars($product['stock']); ?>">
                                    <div class="form-group">
                                        <input type="number" name="quantity" min="1" value="1" class="form-control mb-3 quantity-input" data-stock="<?php echo htmlspecialchars($product['stock']); ?>">
                                    </div>
                                    <button type="submit" name="add_to_cart" class="button btn btn-primary add-to-cart-btn">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">No products found.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Positif. All Rights Reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.quantity-input').on('input', function() {
                const stock = $(this).data('stock');
                const quantity = $(this).val();
                const addToCartBtn = $(this).closest('form').find('.add-to-cart-btn');
                
                if (parseInt(quantity) > parseInt(stock)) {
                    addToCartBtn.prop('disabled', true);
                    alert('Stock is insufficient.');
                } else {
                    addToCartBtn.prop('disabled', false);
                }
            });
        });
    </script>
</body>
</html>
