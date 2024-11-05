<?php
session_start();

// Check if the user is logged in and the form is submitted
if (!isset($_SESSION['uID']) || $_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php'); // Redirect to home page if user is not logged in or form is not submitted
    exit();
}

// Get form data
$uID = $_POST['uID'];
$aDate = $_POST['aDate'];
$aTime = $_POST['aTime'];
$aService = $_POST['aService']; // Service Type (e.g., Nails, Hair)
$nailColor = isset($_POST['nailColor']) ? $_POST['nailColor'] : null;
$hairColor = isset($_POST['hairColor']) ? $_POST['hairColor'] : null;
$paymentAmount = 500; // Fixed payment amount
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
        }
        .gcash-header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 1.5rem;
        }
        .gcash-container {
            margin: 50px auto;
            width: 400px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .gcash-logo {
            width: 100px;
            display: block;
            margin: 0 auto 20px;
        }
        .gcash-details {
            text-align: center;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .gcash-amount {
            font-size: 2rem;
            color: #007bff;
        }
        .gcash-input {
            margin: 20px 0;
        }
        .gcash-input label {
            font-weight: bold;
        }
        .gcash-input input {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .gcash-submit {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
        }
        .gcash-submit:hover {
            background-color: #0056b3;
        }
        .gcash-footer {
            margin-top: 20px;
            font-size: 0.9rem;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>

<div class="gcash-header">
    GCash Payment
</div>

<div class="gcash-container">
    <img src="Gcash.jpg" alt="GCash Logo" class="gcash-logo">
    <div class="gcash-details">
        Pay for your salon appointment<br>
        <span class="gcash-amount">₱<?php echo number_format($paymentAmount, 2); ?></span> <!-- Display payment amount -->
    </div>

    <form method="POST" action="process_payment.php">
        <div class="gcash-input">
            <label for="gcashNumber">GCash Mobile Number</label>
            <input type="text" id="gcashNumber" name="gcashNumber" placeholder="Enter your GCash number" pattern="0[0-9]{10}" required>
        </div>

        <div class="gcash-input">
            <label for="gcashPin">GCash PIN</label>
            <input type="password" id="gcashPin" name="gcashPin" placeholder="Enter your 4-digit PIN" pattern="[0-9]{4}" required>
        </div>

        <!-- Hidden fields to pass data to the payment processing script -->
        <input type="hidden" name="uID" value="<?php echo htmlspecialchars($uID); ?>">
        <input type="hidden" name="aDate" value="<?php echo htmlspecialchars($aDate); ?>">
        <input type="hidden" name="aTime" value="<?php echo htmlspecialchars($aTime); ?>">
        <input type="hidden" name="aService" value="<?php echo htmlspecialchars($aService); ?>">
        
        <input type="hidden" name="nailColor" value="<?php echo htmlspecialchars($nailColor); ?>">
        <input type="hidden" name="hairColor" value="<?php echo htmlspecialchars($hairColor); ?>">
        <input type="hidden" name="payment" value="<?php echo htmlspecialchars($paymentAmount); ?>">

        <button type="submit" class="gcash-submit">Pay Now</button>
    </form>

    <div class="gcash-footer">
        Powered by GCash &copy; 2024
    </div>
</div>

</body>
</html>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    // Handle service type selection and show relevant options
    $("#serviceType").change(function() {
        var serviceType = $(this).val();
        $(".service-options").hide(); // Hide all service options
        if (serviceType === 'nails') {
            $("#nailOptions").show();
            
            $("#hairService").removeAttr("name");  // Remove name from hairService
        } else if (serviceType === 'hair') {
            $("#hairOptions").show();
          
            $("#nailService").removeAttr("name");  // Remove name from nailService
        }<?php
