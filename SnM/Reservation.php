<?php
// Start the session
session_start();

// Example: Assuming the user ID is set when the user logs in (ensure this is done in your login script)
if (!isset($_SESSION['uID'])) {
    // Redirect to login page if the user is not logged in
    header('Location: login.php');
    exit;
}

// Example: Reserved appointments (this would come from your database in a real application)
$reservedAppointments = ["2024-09-01", "2024-09-05", "2024-09-12"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salon Appointment</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .navbar-brand img {
            height: 50px;
        }
        .container {
            margin-top: 50px;
            flex: 1;
        }
        footer {
            background-color: #f3e5ab;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        .alert-success {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .color-options img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .color-options img.selected {
            border-color: #007bff;
        }
        .color-options {
            display: none;
        }
        footer {
            background-color: #f3e5ab !important;
            color: black;
            padding: 20px 0;
            text-align: center;
        }
        .navbar-light .navbar-nav .nav-link {
            color: rgba(0,0,0,.5);
        }
        .navbar-light .navbar-nav .nav-link:hover {
            color: rgba(0,0,0,.9);
        }
        .bg-cream {
            background-color: #f3e5ab !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-cream">
    <a class="navbar-brand" href="#"><img src="SnM.jpg" alt="Logo" class="logo"></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="user.php">User</a></li>
            <li class="nav-item"><a class="nav-link" href="Reservation.php">Appointment</a></li>
            <li class="nav-item"><a class="nav-link" href="sign.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <!-- Display the success message -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title">Book Your Appointment</h3>
            <form method="POST" action="payment.php">
                <div class="form-group">
                    <label for="aDate">Select Date</label>
                    <input type="text" class="form-control" name="aDate" id="aDate" required>
                </div>
                <div class="form-group">
                    <label for="aTime">Select Time</label>
                    <input type="time" class="form-control" name="aTime" id="aTime" required>
                </div>
                <div class="form-group">
                    <label for="aService">Select Service</label>
                    <select class="form-control" name="aService" id="aService" required>
                        <option value="" disabled selected>Select Service</option>
                        <option value="Nail Color">Nail Color</option>
                        <option value="Gel Polish">Gel Polish</option>
                        <option value="Nail Art">Nail Art</option>
                        <option value="Hair Cut">Hair Cut</option>
                        <option value="Hair Color">Hair Color</option>
                        <option value="Hair Treatment">Hair Treatment</option>
                    </select>
                </div>

                <!-- Image buttons for Nail Color selection -->
                <div id="nailColorOptions" class="form-group color-options">
                    <label>Select Nail Color</label>
                    <div class="color-options">
                        <img src="red.jpg" alt="Red" data-value="Red" class="color-option">
                        <img src="blue.jpg" alt="Blue" data-value="Blue" class="color-option">
                        <img src="pink.jpg" alt="Pink" data-value="Pink" class="color-option">
                    </div>
                    <input type="hidden" name="nailColor" id="nailColor">
                </div>

                <!-- Image buttons for Hair Color selection -->
                <div id="hairColorOptions" class="form-group color-options">
                    <label>Select Hair Color</label>
                    <div class="color-options">
                        <img src="blonde.jpg" alt="Blonde" data-value="Blonde" class="color-option">
                        <img src="brown.jpg" alt="Brown" data-value="Brown" class="color-option">
                        <img src="black.jpg" alt="Black" data-value="Black" class="color-option">
                    </div>
                    <input type="hidden" name="hairColor" id="hairColor">
                </div>

                <!-- Payment -->
                <div class="form-group">
                    <label for="payment">Payment</label>
                    <input type="number" class="form-control" name="payment" value="500" readonly>
                </div>

                <!-- Hidden field to pass uID from session -->
                <input type="hidden" name="uID" value="<?php echo $_SESSION['uID']; ?>">

                <button type="submit" class="btn btn-primary">Book Appointment</button>
            </form>
        </div>
    </div>
</div>

<footer>
    &copy; 2024 Your Salon. All Rights Reserved.
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    $(function() {
        // Fetch reserved appointments (passed from server-side PHP)
        var reservedAppointments = <?php echo json_encode($reservedAppointments); ?>;

        // Datepicker initialization and disabling reserved dates
        $("#aDate").datepicker({
            dateFormat: 'yy-mm-dd',
            beforeShowDay: function(date) {
                var string = $.datepicker.formatDate('yy-mm-dd', date);
                return [reservedAppointments.indexOf(string) == -1, "disabled-date"];
            }
        });

        // Service selection logic to show corresponding color options
        $("#aService").change(function() {
            var selectedService = $(this).val();
            
            // Hide both color options by default
            $("#nailColorOptions").hide();
            $("#hairColorOptions").hide();
            
            // Show nail color options if "Nail Color" is selected
            if (selectedService === 'Nail Color') {
                $("#nailColorOptions").show();
            }
            // Show hair color options if "Hair Color" is selected
            else if (selectedService === 'Hair Color') {
                $("#hairColorOptions").show();
            }
        });

        // Handle image button selection for Nail Color and Hair Color
        $(".color-option").click(function() {
            var selectedOption = $(this).data("value");
            
            // Remove selected class from all images and add to the clicked image
            $(this).siblings().removeClass("selected");
            $(this).addClass("selected");

            // Update the hidden input value for the nail or hair color
            if ($(this).closest("#nailColorOptions").length) {
                $("#nailColor").val(selectedOption);
            } else if ($(this).closest("#hairColorOptions").length) {
                $("#hairColor").val(selectedOption);
            }
        });

        // Hide success message after 3 seconds
        setTimeout(function() {
            $('.alert-success').fadeOut('slow');
        }, 3000);
    });
</script>

</body>
</html>
