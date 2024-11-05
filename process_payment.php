<?php
session_start();

// Check if the user is logged in and if appointment data exists in the session
if (!isset($_SESSION['uID']) || !isset($_SESSION['appointment'])) {
    header('Location: reservation.php?error=session_missing');
    exit();
}

// Retrieve the appointment data from session
$appointment = $_SESSION['appointment'];

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sam";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Extract common appointment data
$uID = intval($appointment['uID']);
$aDate = $appointment['aDate'];
$aTime = $appointment['aTime'];
$aService = $appointment['aService'];
$servicePrice = $appointment['servicePrice']; // Total amount
$paymentStatus = isset($appointment['payment']) ? $appointment['payment'] : 'pending'; // Assuming payment status is 'pending' before payment completion
$paymentType = 'gcash'; // Assuming the payment is always through GCash

// Begin transaction to ensure atomicity
$conn->begin_transaction();

try {
    $insertedID = null; // To store the last inserted aID or nID

    // Handle appointment based on the service type (nail or hair)
    if ($appointment['serviceType'] == 'nail') {
        // Nail appointment data
        $nColor = isset($appointment['nailColor']) ? $appointment['nailColor'] : null;

        // Insert nail appointment into the nappointment table
        $stmt = $conn->prepare("INSERT INTO nappointment (nService, nColor, nTime, nDate, ntimestamp, nstatus, uID, nPayment) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $nStatus = 'reserved'; // Assuming the status is 'reserved' at the time of creation
        $stmt->bind_param('sssssis', $aService, $nColor, $aTime, $aDate, $nStatus, $uID, $paymentStatus);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting nail appointment: " . $stmt->error);
        }
        // Get the inserted nID
        $insertedID = $conn->insert_id;
        $stmt->close();

    } elseif ($appointment['serviceType'] == 'hair') {
        // Hair appointment data
        $hairColor = isset($appointment['hairColor']) ? $appointment['hairColor'] : null;

        // Insert hair appointment into the appointment table
        $stmt = $conn->prepare("INSERT INTO appointment (aDate, aService, hairColor, aTime, atimestamp, Status, uID, payment) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $aStatus = 'reserved'; // Assuming the status is 'reserved' at the time of creation
        $stmt->bind_param('sssssis', $aDate, $aService, $hairColor, $aTime, $aStatus, $uID, $paymentStatus);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting hair appointment: " . $stmt->error);
        }
        // Get the inserted aID
        $insertedID = $conn->insert_id;
        $stmt->close();
    } else {
        throw new Exception("Invalid service type.");
    }

    // Insert payment data into the payment table with the extracted aID or nID
    $stmt = $conn->prepare("INSERT INTO payment (pAmount, pType, uID, pTime, aID) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->bind_param('dsii', $servicePrice, $paymentType, $uID, $insertedID);

    if (!$stmt->execute()) {
        throw new Exception("Error inserting payment: " . $stmt->error);
    }
    $stmt->close();

    // If the total service price exceeds 500, update the user's stamp count
    if ($servicePrice > 500) {
        $stmt = $conn->prepare("UPDATE login SET stamps = stamps + 1 WHERE uID = ?");
        $stmt->bind_param('i', $uID);

        if (!$stmt->execute()) {
            throw new Exception("Error updating user stamps: " . $stmt->error);
        }
        $stmt->close();
    }

    // Commit the transaction
    $conn->commit();

    // Redirect to reservation.php with a success message
    header("Location: reservation.php?payment=success");
    exit();

} catch (Exception $e) {
    // Rollback the transaction in case of any error
    $conn->rollback();
    echo "Transaction failed: " . $e->getMessage();
}

// Close the database connection
$conn->close();

// Optional: Clear the session appointment data after saving
unset($_SESSION['appointment']);
?>
