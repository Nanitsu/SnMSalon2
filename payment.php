<?php
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure user is logged in and has selected an appointment
if (!isset($_SESSION['uID']) || !isset($_SESSION['appointment'])) {
    header('Location: index.php');
    exit();
}

// Retrieve appointment data from session
$appointment = $_SESSION['appointment'];

// Validate that necessary data exists
if (!isset($appointment['servicePrice']) || !isset($appointment['aService'])) {
    header('Location: reservation.php?error=missing_data');
    exit();
}

$servicePrice = floatval($appointment['servicePrice']);
$aService = htmlspecialchars($appointment['aService'], ENT_QUOTES, 'UTF-8');

// Further validation
if ($servicePrice <= 0 || empty($aService)) {
    header('Location: reservation.php?error=invalid_data');
    exit();
}

// Convert servicePrice to centavos for PayMongo API
$paymentAmount = intval(round($servicePrice * 100)); // Amount in centavos

// Debugging: Log the servicePrice and aService
error_log("Service Price: PHP " . $servicePrice);
error_log("Service Selected: " . $aService);

// PayMongo API credentials and endpoint
$apiUrl = 'https://api.paymongo.com/v1/checkout_sessions';
$apiKey = 'sk_test_z59adBbBf47vmVkm6KGYgRcb'; // Replace with your actual PayMongo secret key

// Prepare data for creating a checkout session
$checkoutData = [
    'data' => [
        'attributes' => [
            'cancel_url' => 'http://localhost/SnM/reservation.php?payment=cancel',
            'success_url' => 'http://localhost/SnM/process_payment.php?session_id={CHECKOUT_SESSION_ID}',
            'line_items' => [ // Correct parameter name: 'line_items'
                [
                    'name' => $aService, // Added 'name' field
                    'amount' => $paymentAmount, // Amount in centavos
                    'currency' => 'PHP',
                    'description' => 'Appointment Payment for ' . $aService,
                    'quantity' => 1,
                ]
            ],
            'payment_method_types' => ['gcash'],
        ]
    ]
];

// Convert data to JSON
$checkoutDataJson = json_encode($checkoutData);

// Debugging: Log the checkout data
error_log("Checkout Data JSON: " . $checkoutDataJson);

// Initialize cURL to make the API request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode($apiKey . ':')
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $checkoutDataJson);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    $curlError = curl_error($ch);
    error_log("cURL Error: " . $curlError);
    // Redirect to an error page or show an error message
    header('Location: reservation.php?error=curl');
    exit();
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode the response
$responseArray = json_decode($response, true);

// Debugging: Log the response
error_log("PayMongo Response HTTP Code: " . $httpCode);
error_log("PayMongo Response Body: " . $response);

// Check if the request was successful
if ($httpCode == 201 || $httpCode == 200) {
    if (isset($responseArray['data']['attributes']['checkout_url'])) {
        $checkoutUrl = $responseArray['data']['attributes']['checkout_url'];
        $checkoutSessionId = $responseArray['data']['id'];

        // Save the checkout session ID to session for later verification if needed
        $_SESSION['checkout_session_id'] = $checkoutSessionId;

        // Redirect user to the payment checkout URL
        header("Location: $checkoutUrl");
        exit();
    } else {
        error_log("PayMongo Error: checkout_url not found in response.");
        // Redirect to an error page or show an error message
        header('Location: reservation.php?error=checkout_url');
        exit();
    }
} else {
    // Extract error message from PayMongo response
    $errorMessage = isset($responseArray['errors'][0]['detail']) ? $responseArray['errors'][0]['detail'] : 'Unknown error occurred.';
    // Log the PayMongo API error
    error_log("PayMongo API Error: " . $errorMessage);
    // Redirect to an error page or show an error message
    header("Location: reservation.php?error=api&message=" . urlencode($errorMessage));
    exit();
}
?>
