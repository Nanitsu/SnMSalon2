<?php
// Start session
session_start();

// Ensure user is logged in and is an admin
if (!isset($_SESSION['uID'])) {
    header('Location: sign.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: url('adminbg.jpg') no-repeat center center fixed;
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
            margin-right: 82%;
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

        .dashboard-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            margin-top: 50px;
        }

        .footer {
            background-color: #002f6c;
            color: #f0c27b;
            width: 100%;
            padding: 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
            position: sticky;
            bottom: 0;
            left: 0;
            box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.2);
            z-index: 10;
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
         /* Adjust title to align center when burger menu is on the left */
        .app-bar-content {
            display: flex;
            align-items: center;
            flex: 1;
            justify-content: center;
        }

        /* Added logo styling */
        .logo {
            height: 50px;
            margin-right: 20px;
        }
    </style>
</head>

<body>
    <div class="app-bar">
         <img src="SnM.jpg" alt="Salon Logo" class="logo">
        <div class="burger-menu" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="title">Admin Dashboard</div>
        <div class="nav-links">
          
            <a href="admin.php">Manage Users</a>
            <a href="walkin.php">Walk in Records </a>
            <a href="aduttrail.php">Audittrail</a>
            <a href="appointments.php">Appointment Records</a>
          
            <a href="sign.php">Logout</a>
        </div>
    </div>

    <div class="dashboard-container">
        <h1>Welcome to the Admin Dashboard</h1>
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Toggle navigation menu
        function toggleMenu() {
            const menu = document.querySelector('.nav-links');
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
        }

    </script>
</body>

</html>
