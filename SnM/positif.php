<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Information Page</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            font-family: 'Arial', sans-serif;
            color: #333;
        }
        .info-frame {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin: 20px;
            padding: 40px;
            width: 100%;
            max-width: 800px;
        }
        .header-title {
            font-size: 2.5rem;
            margin-bottom: 30px;
            text-align: center;
        }
        .section-title {
            margin-top: 20px;
            font-size: 1.5rem;
            font-weight: bold;
            border-bottom: 2px solid #ff7e5f;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .content {
            margin-top: 10px;
            line-height: 1.6;
        }
        .navbar-brand img {
            height: 60px;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        footer {
            background-color: #f3e5ab !important;
            color: black;
            text-align: center;
            padding: 20px 0;
        }
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
        <div class="info-frame">
            <h1 class="header-title">About Our Company</h1>
            
            <h2 class="section-title">Company Overview</h2>
            <p class="content">
                Founded in 2003, our company has evolved into a well-established corporation. We started our operations with supplying raw materials, commodity and specialty ingredients to cater to the needs of food, beverage, pharmaceuticals, personal/home care, and nutraceuticals industry. By mid-2005, our company decided to focus on specialty ingredients and functional raw materials for the growing segments of these industries. We also added another division, the Industrial Division, which deals with the supply of materials, such as cement, steel, copper, glass, and petrochemicals, and services for the heavy industries. The original ingredients trading business was then referred to as Food & Chemicals Division. 
                
                With our core principals in food based in Europe, our company was able to establish businesses with major companies in the Philippines. Our Industrial Division grew phenomenally that we became one of the major installers of refractories in the country today and one of the best candidates for a reliable partner for refractory materials and other process auxiliary materials needs.
            </p>
            
            <h2 class="section-title">Mission</h2>
            <p class="content">
                - To capture and successfully complete at customers satisfaction, specialty and related construction projects in major local companies.<br>
                - To professionally and profitably represent in the Philippines major global foreign suppliers of industrial products.<br>
                - To provide employees and shareholders fair share of returns for their contributions and to give each a sense of pride in their being with the company.
            </p>
            
            <h2 class="section-title">Vision</h2>
            <p class="content">
                A dynamic, institutionalized and reliable specialized construction and trading company manned by highly motivated and happy people delivering positive business results at all times.
            </p>
            
            <h2 class="section-title">Corporate Profile</h2>
            <p class="content">
                We have been incorporated on August 28, 2003. We have "Trading", "Engineering Services", and "Construction" as nature of business. Our business address is at 33 Verdant Avenue, Verdant Acres, Pamplona 3, Las Pinas City 1740 Metro Manila, Philippines.
            </p>
            
            <h2 class="section-title">Contact Information</h2>
            <p class="content">
                Address: 33 Verdant Avenue, Verdant Acres Pamplona 3, Las Pinas City 1740 Metro Manila, Philippines <br>
                Phone: Tel: (+632) 872-1928 | Fax: (+632) 872-1928<br>
                Email: positif@positifcorp.com
            </p>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2024 Positif Corp. All Rights Reserved.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
