<?php
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php'); // Redirect if accessed directly
    exit();
}

// Validate GCash Number and PIN
$gcashNumber = $_POST['gcashNumber'];
$gcashPin = $_POST['gcashPin'];

// GCash number must be 11 digits starting with '0'
if (!preg_match('/^0\d{10}$/', $gcashNumber)) {
    $_SESSION['message'] = "Invalid GCash number. Please try again.";
    header("Location: payment.php");
    exit();
}

// GCash PIN must be 4 digits
if (strlen($gcashPin) !== 4 || !is_numeric($gcashPin)) {
    $_SESSION['message'] = "Invalid GCash PIN. Please try again.";
    header("Location: payment.php");
    exit();
}

// Now, after validation, proceed to book the appointment

// Get appointment details from the form
$uID = $_POST['uID'];
$aDate = $_POST['aDate'];
$aTime = $_POST['aTime'];
$aService = $_POST['aService'];
$nailColor = isset($_POST['nailColor']) ? $_POST['nailColor'] : null;
$hairColor = isset($_POST['hairColor']) ? $_POST['hairColor'] : null;
$paymentAmount = 500; // Fixed payment amount

// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sam";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Insert the appointment into the database after payment is confirmed
    $stmt = $conn->prepare("INSERT INTO appointment (uID, aDate, aTime, aService, nailColor, hairColor, payment, Status) 
                            VALUES (:uID, :aDate, :aTime, :aService, :nailColor, :hairColor, :payment, 'active')");
    $stmt->execute([
        'uID' => $uID,
        'aDate' => $aDate,
        'aTime' => $aTime,
        'aService' => $aService,
        'nailColor' => $nailColor,
        'hairColor' => $hairColor,
        'payment' => $paymentAmount
    ]);

    // Show success message and redirect to confirmation page
    $_SESSION['message'] = "Your appointment has been successfully booked!";
    header("Location: Reservation.php");
    exit();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
