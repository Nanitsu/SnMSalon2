<?php
session_start();

if (!isset($_SESSION['reserveDate']) || !isset($_SESSION['payment'])) {
    header("Location: reservation.php");
    exit();
}

// Retrieve reservation details from session
$reserveDate = $_SESSION['reserveDate'];
$payment = $_SESSION['payment'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $gcashNumber = $_POST['gcashNumber'];

    // Validate GCash number
    if (!preg_match("/^\d{11}$/", $gcashNumber)) {
        $message = "Invalid GCash number. Please enter an 11-digit number.";
    } else {
        // Process payment (dummy processing code)
        $paymentSuccess = true; // Simulate payment success

        if ($paymentSuccess) {
            // Clear reservation details from session after successful payment
            unset($_SESSION['reserveDate']);
            unset($_SESSION['payment']);
            $message = "Payment successful! Your reservation for " . $reserveDate . " has been confirmed.";
        } else {
            $message = "Payment failed. Please try again.";
        }
    }

    // Store the message in the session to display it after redirect
    $_SESSION['message'] = $message;

    // Redirect to the same page to show the message
    header("Location: reserve_payment.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reservation Payment</title>
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
        .order-summary {
            margin-bottom: 20px;
        }
        .order-summary h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .order-summary ul {
            list-style: none;
            padding: 0;
        }
        .order-summary ul li {
            background: #e0f2ff;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
        }
        .order-summary .total {
            font-weight: bold;
            font-size: 1.2rem;
        }
        .btn-success {
            background-color: #0072ff;
            border-color: #0072ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success" id="message-alert">
                <?php
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        <div class="order-summary">
            <h3>Reservation Summary</h3>
            <ul>
                <li><span>Date:</span><span><?php echo $reserveDate; ?></span></li>
                <li class="total"><span>Amount:</span><span>₱<?php echo $payment; ?></span></li>
            </ul>
        </div>
        <form method="post">
            <div class="form-group">
                <label for="gcashNumber">GCash Number:</label>
                <input type="text" class="form-control" id="gcashNumber" name="gcashNumber" required pattern="\d{11}" title="Please enter an 11-digit number">
            </div>
            <input type="hidden" name="totalAmount" value="<?php echo $payment; ?>">
            <input type="submit" class="btn btn-success btn-block" value="Pay Now">
            <button type="button" class="btn btn-secondary btn-block" onclick="goBack()">Back</button>
        </form>
    </div>
    <script>
        function goBack() {
            window.location.href = 'reservation.php';
        }

        // Hide the message after a few seconds
        setTimeout(function() {
            document.getElementById("message-alert").style.display = 'none';
        }, 5000);
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
