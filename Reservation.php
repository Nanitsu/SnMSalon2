<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['uID'])) {
    header('Location: sign.php');
    exit();
}

// Initialize variables to avoid undefined variable warnings
$selectedTime = isset($_POST['aTime']) ? $_POST['aTime'] : '';
$selectedNailColor = isset($_POST['nColor']) ? $_POST['nColor'] : '';
$selectedHairColor = isset($_POST['hairColor']) ? $_POST['hairColor'] : '';
$servicePrice = 0; // Initialize the total service price
$additionalColorCost = 300; // Additional price for color selection

// Handle payment status messages
if (isset($_GET['payment'])) {
    if ($_GET['payment'] == 'success') {
        $successMessage = "Your payment was successful!";
    } elseif ($_GET['payment'] == 'cancel') {
        $warningMessage = "Your payment was canceled.";
    } elseif ($_GET['payment'] == 'error') {
        $warningMessage = "There was an error processing your payment.";
    }
}

// Create a database connection (this must be done before fetching or inserting data)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sam";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Reserved appointments (fetch from your database)
$reservedAppointments = [];

// Fetch reserved dates
$sql = "SELECT aDate FROM appointment UNION SELECT nDate FROM nappointment";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservedAppointments[] = $row['aDate'];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uID = $_SESSION['uID']; // Use uID from session
    $aDate = $_POST['aDate'];
    $aTime = $_POST['aTime'];

    // Get selected service and type
    $aService = isset($_POST['aService']) ? $_POST['aService'] : null;
    $serviceType = isset($_POST['serviceType']) ? $_POST['serviceType'] : null;

    if ($aService && $serviceType) {
        // Handle Nail Services
        if ($serviceType == 'nail') {
            $nailColor = isset($_POST['nColor']) ? $_POST['nColor'] : null;

            // Set price based on selected service
            if ($aService == "Nail Color") $servicePrice = 300;
            if ($aService == "Manicure + Pedicure Nail Color") $servicePrice = 500;
            if ($aService == "Pedicure Nail Color") $servicePrice = 300;

            // Add the color cost if a color is selected
            if ($nailColor && $nailColor != 'None') {
                $servicePrice += $additionalColorCost;
            }

            // Store selected service details in session
            $_SESSION['appointment'] = [
                'uID' => $uID,
                'aDate' => $aDate,
                'aTime' => $aTime,
                'aService' => $aService,
                'nailColor' => $nailColor,
                'servicePrice' => $servicePrice,
                'serviceType' => 'nail'
            ];

            // Redirect to payment.php for processing payment
            header("Location: payment.php");
            exit();

        } elseif ($serviceType == 'hair') {
            // Handle Hair Services
            $hairColor = isset($_POST['hairColor']) ? $_POST['hairColor'] : null;

            // Set price based on selected service
            if ($aService == "Hair Cut + Hair Color") $servicePrice = 300;
            if ($aService == "Hair Cut + Hair Treatment") $servicePrice = 650;
            if ($aService == "Hair Cut") $servicePrice = 150;
            if ($aService == "Hair Treatment") $servicePrice = 600;

            // Add the color cost if a color is selected
            if ($hairColor && $hairColor != 'None') {
                $servicePrice += $additionalColorCost;
            }

            // Store selected service details in session
            $_SESSION['appointment'] = [
                'uID' => $uID,
                'aDate' => $aDate,
                'aTime' => $aTime,
                'aService' => $aService,
                'hairColor' => $hairColor,
                'servicePrice' => $servicePrice,
                'serviceType' => 'hair'
            ];

            // Redirect to payment.php for processing payment
            header("Location: payment.php");
            exit();
        }
    } else {
        $warningMessage = "Please select a service.";
    }
}

// Close the database connection only after you are done with it
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salon Appointment</title>
    <!-- Include Bootstrap CSS and other necessary styles -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include custom CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <!-- Include jQuery and jQuery UI for datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        /* CSS styles */

        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: url('userbg.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .app-bar {
            background-color: #002f6c;
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #f0c27b;
            font-size: 24px;
            font-family: 'Playfair Display', serif;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .burger-menu {
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }

        .burger-menu div {
            width: 30px;
            height: 4px;
            background-color: #f0c27b;
            margin: 5px 0;
        }

        .nav-links {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 70px;
            left: 0;
            background-color: #002f6c;
            width: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-links a {
            color: #f0c27b;
            text-decoration: none;
            padding: 10px 20px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #e0975f;
        }

        .burger-menu.active + .nav-links {
            display: flex;
        }

        .title {
            font-weight: bold;
        }

        .main-content {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            margin-top: 20px;
            flex-grow: 1;
            flex-wrap: wrap;
            padding: 0 20px;
        }

        .image-container {
            flex: 0 0 40%;
            margin-right: 20px;
        }

        .image-container img {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }

        .content-container {
            flex: 1;
            width: 100%;
        }

        .container {
            width: 120%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            backdrop-filter: blur(10px);
            margin: 20px auto;
            margin-left: 0%;
            margin-top: 0%;

        }

        .form-group label {
            color: #002f6c;
            font-weight: bold;
        }

        .form-control {
            border: 2px solid #e0975f;
            border-radius: 30px;
            padding: 15px;
            width: 30%;
            font-size: 16px;
            color: #333;
            background: #fff;
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 20%;
            margin-left: 35%;
        }

        .form-control:focus {
            border-color: #f0c27b;
            box-shadow: 0 0 10px rgba(240, 194, 123, 0.5);
            outline: none;
        }

        .btn-primary {
            background-color: #002f6c;
            color: #f0c27b;
            border: none;
            border-radius: 30px;
            font-weight: bold;
            padding: 15px;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-primary:hover {
            background-color: #e0975f;
            color: #002f6c;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
        }

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            width: 100%;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            
            bottom: 0;
            left: 0;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
            margin-top: auto;
        }

        .footer-links a {
            color: #f0c27b;
            margin: 0 15px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #e0975f;
        }

        .service-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .service-container {
            width: 200px;
            padding: 10px;
            border-radius: 15px;
            background-color: #f0f0f0;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .service-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        .color-options {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeeba;
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .image-container, .content-container {
                flex: 1 0 100%;
                margin-right: 0;
            }

            .image-container {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="app-bar">
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Scissors and Mirrors Salon - Appointment</div>
        <div class="nav-links">
            <a href="profile.php">Profile</a>
            <a href="user.php">User</a>
            <a href="reservation.php">Appointment</a>
            <a href="sign.php">Logout</a>
            <a href="paymentrec.php">Payments</a>
        </div>
    </div>

    <div class="main-content">
        <!-- Image container on the left -->
        <div class="image-container">
            <img src="saloninterior.jpg" alt="Salon Interior">
        </div>

        <!-- Content container on the right -->
        <div class="content-container">
            <div class="container">
                <h3>Select a Service</h3>
                <?php if (isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $successMessage; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($warningMessage)): ?>
                    <div class="alert alert-warning">
                        <?php echo $warningMessage; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">

                    <!-- Common Date Picker -->
                    <div class="form-group">
                        <label for="aDate">Select Date</label>
                        <input type="text" class="form-control" name="aDate" id="aDate" required>
                    </div>

                    <!-- Time Selection -->
                    <div class="form-group">
                        <label for="aTime">Select Time</label>
                        <select class="form-control" name="aTime" id="aTime" required>
                            <option value="09:00">09:00 AM - 11:00 AM</option>
                            <option value="11:00">11:00 AM - 01:00 PM</option>
                            <option value="13:00">01:00 PM - 03:00 PM</option>
                            <option value="15:00">03:00 PM - 05:00 PM</option>
                            <option value="17:00">05:00 PM - 07:00 PM</option>
                        </select>
                    </div>

                    <!-- Service Selection -->
                    <div class="form-group">
                        <label>Select a Service</label>
                        <div class="service-options">
                            <!-- Nail Services -->
                            <div class="service-container">
                                <img src="mani.jpg" alt="Nail Color" class="service-img">
                                <input type="radio" name="aService" value="Nail Color" data-type="nail" id="nailColor">
                                <label for="nailColor">Manicure (₱300)</label>
                            </div>
                            <div class="service-container">
                                <img src="manipedi.jpg" alt="Manicure + Pedicure" class="service-img">
                                <input type="radio" name="aService" value="Manicure + Pedicure Nail Color" data-type="nail" id="maniPedi">
                                <label for="maniPedi">Manicure + Pedicure (₱500)</label>
                            </div>
                            <div class="service-container">
                                <img src="pedi.jpg" alt="Pedicure Color" class="service-img">
                                <input type="radio" name="aService" value="Pedicure Nail Color" data-type="nail" id="pedicure">
                                <label for="pedicure">Pedicure (₱300)</label>
                            </div>
                            <!-- Hair Services -->
                            <div class="service-container">
                                <img src="cutcolor.jpg" alt="Hair Cut + Hair Color" class="service-img">
                                <input type="radio" name="aService" value="Hair Cut + Hair Color" data-type="hair" id="hairCutColor">
                                <label for="hairCutColor">Hair Cut + Head Massage (₱300)</label>
                            </div>
                            <div class="service-container">
                                <img src="haircuttreat.jpg" alt="Hair Cut + Hair Treatment" class="service-img">
                                <input type="radio" name="aService" value="Hair Cut + Hair Treatment" data-type="hair" id="hairCutTreatment">
                                <label for="hairCutTreatment">Hair Cut + Hair Treatment (₱650)</label>
                            </div>
                            <div class="service-container">
                                <img src="hcut.jpg" alt="Hair Cut" class="service-img">
                                <input type="radio" name="aService" value="Hair Cut" data-type="hair" id="hairCut">
                                <label for="hairCut">Hair Cut (₱150)</label>
                            </div>
                            <div class="service-container">
                                <img src="hspa.jpg" alt="Hair Treatment" class="service-img">
                                <input type="radio" name="aService" value="Hair Treatment" data-type="hair" id="hairTreatment">
                                <label for="hairTreatment">Hair Treatment (₱600)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field to store service type -->
                    <input type="hidden" name="serviceType" id="serviceType" value="">

                    <!-- Color Selection for Nail Services -->
                    <div id="colorSelectionNail" class="form-group" style="display:none;">
                        <label>Select Nail Color (additional ₱300)(Optional)</label>
                        <div class="color-options">
                            <label><input type="radio" name="nColor" value="Red"> Red</label>
                            <label><input type="radio" name="nColor" value="Pink"> Pink</label>
                            <label><input type="radio" name="nColor" value="Blue"> Blue</label>
                            <label><input type="radio" name="nColor" value="Green"> Green</label>
                            <label><input type="radio" name="nColor" value="None"> None</label>
                        </div>
                    </div>

                    <!-- Color Selection for Hair Services -->
                    <div id="colorSelectionHair" class="form-group" style="display:none;">
                        <label>Select Hair Color (additional ₱300)(Optional)</label>
                        <div class="color-options">
                            <label><input type="radio" name="hairColor" value="Blonde"> Blonde</label>
                            <label><input type="radio" name="hairColor" value="Brown"> Brown</label>
                            <label><input type="radio" name="hairColor" value="Black"> Black</label>
                            <label><input type="radio" name="hairColor" value="Red"> Red</label>
                            <label><input type="radio" name="hairColor" value="None"> None</label>
                        </div>
                    </div>

                    <!-- Payment Field -->
                    <div class="form-group">
                        <label for="payment">Total Price (₱)</label>
                        <input type="number" class="form-control" name="payment" id="payment" value="0" readonly>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="submitService" class="btn btn-primary">Book Appointment</button>
                </form>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2024 Scissors and Mirrors Salon. All Rights Reserved.</p>
        <div class="footer-links">
            <a href="#">About Us</a>
            <a href="#">Services</a>
            <a href="#">Contact</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>

    <!-- Include jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $(function () {
            var reservedAppointments = <?php echo json_encode($reservedAppointments); ?>;

            // Initialize datepicker
            $("#aDate").datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                beforeShowDay: function (date) {
                    var string = $.datepicker.formatDate('yy-mm-dd', date);
                    return [reservedAppointments.indexOf(string) == -1];
                }
            });

            // Show/hide color selections based on selected service
            $('input[name="aService"]').change(function () {
                var serviceType = $(this).data('type');
                $('#serviceType').val(serviceType); // Set hidden field

                if (serviceType === 'nail') {
                    $('#colorSelectionNail').show();
                    $('#colorSelectionHair').hide();
                } else if (serviceType === 'hair') {
                    $('#colorSelectionHair').show();
                    $('#colorSelectionNail').hide();
                }

                calculatePrice();
            });

            // Recalculate price when options change
            $('input[name="nColor"], input[name="hairColor"], input[name="aService"]').change(function () {
                calculatePrice();
            });

            // Function to calculate total price
            function calculatePrice() {
                var servicePrice = 0;
                var colorPrice = 300; // Additional price for color selection

                var selectedService = $('input[name="aService"]:checked').val();
                var serviceType = $('input[name="aService"]:checked').data('type');

                if (serviceType === 'nail') {
                    if (selectedService === "Nail Color") {
                        servicePrice = 300;
                    } else if (selectedService === "Manicure + Pedicure Nail Color") {
                        servicePrice = 500;
                    } else if (selectedService === "Pedicure Nail Color") {
                        servicePrice = 300;
                    }

                    var selectedColor = $('input[name="nColor"]:checked').val();

                } else if (serviceType === 'hair') {
                    if (selectedService === "Hair Cut + Hair Color") {
                        servicePrice = 300;
                    } else if (selectedService === "Hair Cut + Hair Treatment") {
                        servicePrice = 650;
                    } else if (selectedService === "Hair Cut") {
                        servicePrice = 150;
                    } else if (selectedService === "Hair Treatment") {
                        servicePrice = 600;
                    }

                    var selectedColor = $('input[name="hairColor"]:checked').val();
                }

                if (selectedColor && selectedColor !== "None") {
                    servicePrice += colorPrice;
                }

                $('#payment').val(servicePrice);
            }

            // Initialize price calculation
            calculatePrice();
        });

        // Toggle navigation menu
        function toggleMenu() {
            const menu = document.querySelector('.nav-links');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }

        // Auto-hide alerts after 3 seconds
        $(document).ready(function () {
            setTimeout(function () {
                $(".alert").fadeOut(500); // Fade out in 0.5 seconds
            }, 3000); // 3 seconds delay
        });
    </script>
</body>
</html>
