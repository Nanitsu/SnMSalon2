<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Ensure user is logged in
if (!isset($_SESSION['uID'])) {
    header("Location: sign.php");
    exit();
}

// Initialize cart if not already set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_quantity'])) {
        // Update quantity
        $index = $_POST['index'];
        $new_quantity = (int)$_POST['new_quantity'];
        if ($new_quantity > 0) {
            $_SESSION['cart'][$index]['quantity'] = $new_quantity;
        } else {
            unset($_SESSION['cart'][$index]);
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    } elseif (isset($_POST['delete_item'])) {
        // Delete item
        $index = $_POST['index'];
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    } elseif (isset($_POST['add_to_cart'])) {
        // Add to cart
        $product_id = $_POST['product_id'];
        $product_name = $_POST['product_name'];
        $product_price = $_POST['product_price'];
        $product_image = $_POST['product_image'];
        $quantity = (int)$_POST['quantity'];

        // Check if the product is already in the cart
        $product_found = false;
        foreach ($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['id'] == $product_id) {
                $cart_item['quantity'] += $quantity;
                $product_found = true;
                break;
            }
        }

        // If the product is not in the cart, add it
        if (!$product_found) {
            $_SESSION['cart'][] = [
                'id' => $product_id,
                'name' => $product_name,
                'price' => $product_price,
                'image' => $product_image,
                'quantity' => $quantity
            ];
        }
    }
}

$cartItems = $_SESSION['cart'];
$totalAmount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
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
        .cart-frame {
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
        .cart-frame:hover {
            transform: translateY(-5px);
        }
        .cart-image {
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
        .btn-danger-custom {
            background-color: #dc3545;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-danger-custom:hover {
            background-color: #c82333;
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
        <h2>Shopping Cart</h2>
        <div class="row">
            <?php if (!empty($cartItems)): ?>
                <?php foreach ($cartItems as $index => $item): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="cart-frame">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-image">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p>Price: ₱<?php echo htmlspecialchars($item['price']); ?></p>
                            <form method="post" class="mb-3">
                                <input type="hidden" name="index" value="<?php echo htmlspecialchars($index); ?>">
                                <input type="number" name="new_quantity" min="1" value="<?php echo htmlspecialchars($item['quantity']); ?>" class="form-control mb-2">
                                <button type="submit" name="update_quantity" class="button">Update Quantity</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="index" value="<?php echo htmlspecialchars($index); ?>">
                                <button type="submit" name="delete_item" class="btn btn-danger-custom">Delete Item</button>
                            </form>
                            <p>Total: ₱<?php echo htmlspecialchars($item['price'] * $item['quantity']); ?></p>
                            <?php $totalAmount += $item['price'] * $item['quantity']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <p>Your cart is empty.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($cartItems)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <h3>Total Amount: ₱<?php echo htmlspecialchars($totalAmount); ?></h3>
                    <form method="post" action="payment.php">
                        <input type="hidden" name="totalAmount" value="<?php echo htmlspecialchars($totalAmount); ?>">
                        <button type="submit" class="button">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <footer>
        <p>&copy; 2024 Positif. All Rights Reserved.</p>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
