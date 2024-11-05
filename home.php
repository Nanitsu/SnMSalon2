<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scissors and Mirrors Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background-color: #fff;
            color: #333;
        }

        .app-bar {
            background-color: #002f6c;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 32px;
            color: #f0c27b; /* Gold color */
            font-family: 'Playfair Display', serif;
            font-weight: bold;
        }

        .button {
            background-color: #f0c27b; /* Gold color */
            color: #002f6c;
            border: none;
            border-radius: 30px;
            padding: 14px 28px;
            font-size: 18px;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            margin: 0 6px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #d9a83f; /* Darker gold on hover */
        }

        .button:focus {
            outline: none;
        }

        .hero-section {
            position: relative;
            width: 100%;
            height: 90vh;
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(70%);
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
        }

        .content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #fff;
        }

        .content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 600;
            margin: 0 0 20px;
            color: #f0c27b; /* Gold color */
        }

        .content p {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 400;
            margin: 0 0 40px;
            color: #fff;
        }

        .content .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .services-section {
            padding: 40px 20px;
            background-color: #f7f7f7;
        }

        .services-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            text-align: center;
            margin-bottom: 20px;
            color: #002f6c;
        }

        .services-container {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 20px 0;
            scrollbar-width: thin;
            scrollbar-color: #f0c27b #fff;
        }

        .service-card {
            min-width: 300px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }

        .service-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .service-card h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            margin: 20px;
            color: #002f6c;
        }

        .service-card p {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            margin: 0 20px 20px;
            color: #333;
        }

        .service-card:hover {
            transform: scale(1.05);
        }

        .footer {
            background-color: #002f6c;
            color: #fff;
            padding: 40px 20px;
            text-align: center;
            font-family: 'Outfit', sans-serif;
        }

        .footer p {
            margin: 0;
            font-size: 16px;
            color: #f0c27b;
        }

        .footer-links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 40px;
            font-size: 18px;
        }

        .footer-links a {
            color: #f0c27b;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #d9a83f;
        }
    </style>
</head>

<body>
    <div class="app-bar">
        <div class="title">Scissors and Mirrors Salon</div>
    </div>

    <div class="hero-section">
        <img src="salonbg.jpg" alt="Salon Interior" class="hero-image">
        <div class="overlay"></div>
        <div class="content">
            <h1>Welcome to Scissors and Mirrors Salon</h1>
            <p>Revitalizing Your Style</p>
            <div class="cta-buttons">
                <button class="button" onclick="window.location.href='sign.php'">Login</button>
                <button class="button" onclick="window.location.href='index.php'">Register</button>
            </div>
        </div>
    </div>

    <div class="services-section">
        <h2 class="services-title">Our Services</h2>
        <div class="services-container">
            <div class="service-card">
                <img src="mani.jpg" alt="Nail Service">
                <h3>Manicure & Pedicure</h3>
                <p>Experience our professional nail services including nail art and treatment for beautiful hands and feet.</p>
            </div>
            <div class="service-card">
                <img src="hcolor.jpg" alt="Hair Coloring">
                <h3>Hair Coloring</h3>
                <p>Get the latest hair colors that perfectly suit your style, done by our skilled stylists.</p>
            </div>
            <div class="service-card">
                <img src="hspa.jpg" alt="Hair Wash">
                <h3>Hair Wash & Treatment</h3>
                <p>Relax and rejuvenate with our luxurious hair wash and treatments for healthy, shiny hair.</p>
            </div>
            <div class="service-card">
                <img src="hcut.jpg" alt="Hairstyling">
                <h3>Hairstyling</h3>
                <p>Look stunning with our professional hairstyling services, perfect for any occasion.</p>
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
</body>

</html>
