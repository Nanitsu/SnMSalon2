<?php
session_start();

if (!isset($_SESSION['paymentSuccess']) || !$_SESSION['paymentSuccess']) {
    header("Location: payment.php");
    exit();
}

if (isset($_SESSION['receiptMessage'])) {
    $receiptMessage = $_SESSION['receiptMessage'];
    unset($_SESSION['receiptMessage']);
    unset($_SESSION['paymentSuccess']);
} else {
    $receiptMessage = "No receipt available.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    
    <title>Order Confirmation</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #00c6ff, #0072ff);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
            background: #f1f9ff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            color: #0072ff;
        }
        .receipt {
            white-space: pre-wrap;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>Order Confirmation</h3>
        <div class="receipt">
            <?php echo nl2br(htmlspecialchars($receiptMessage)); ?>
        </div>
        <button class="btn btn-success btn-block" onclick="window.location.href='order.php'">Continue Shopping</button>
    </div>
</body>
</html>
