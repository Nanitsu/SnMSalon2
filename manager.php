<?php
session_start();

$servername = "localhost";
$username = "Admin";
$password = "Garcia@1234";
$dbname = "sam";

// Establishing connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch user information from the database if the user is logged in
if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} else {
    // Redirect user to login page if not logged in
    header("Location: sign.php");
    exit();
}

// Retrieve sales data for each month
$sales_by_month = array();
for ($month = 1; $month <= 12; $month++) {
    $sales_sql = "SELECT SUM(pAmount) AS total_sales FROM payment WHERE MONTH(pTime) = ?";
    $sales_stmt = $conn->prepare($sales_sql);
    $sales_stmt->execute([$month]);
    $sales_result = $sales_stmt->fetch(PDO::FETCH_ASSOC);
    $total_sales = $sales_result['total_sales'] ?? 0;
    $sales_by_month[$month] = $total_sales;
}

// Retrieve reservations data for each month
$reservations_by_month = array();
for ($month = 1; $month <= 12; $month++) {
    $reservation_sql = "SELECT COUNT(*) AS total_reservations FROM reservation WHERE MONTH(ReserveDate) = ?";
    $reservation_stmt = $conn->prepare($reservation_sql);
    $reservation_stmt->execute([$month]);
    $reservation_result = $reservation_stmt->fetch(PDO::FETCH_ASSOC);
    $total_reservations = $reservation_result['total_reservations'] ?? 0;
    $reservations_by_month[$month] = $total_reservations;
}

// Calculate overall sales
$total_sales_sql = "SELECT SUM(pAmount) AS total_sales FROM payment";
$total_sales_stmt = $conn->query($total_sales_sql);
$total_sales_result = $total_sales_stmt->fetch(PDO::FETCH_ASSOC);
$overall_sales = $total_sales_result['total_sales'] ?? 0;

// Retrieve the most popular item based on available stock (pStock)
$popular_item_sql = "SELECT prodID, pName, pStock, image FROM product ORDER BY pStock ASC LIMIT 1";
$popular_item_stmt = $conn->query($popular_item_sql);
$popular_item = $popular_item_stmt->fetch(PDO::FETCH_ASSOC);
$most_popular_item = $popular_item['pName'] ?? 'N/A';
$most_popular_item_stock = $popular_item['pStock'] ?? 0;
$most_popular_item_sold = 1000 - $most_popular_item_stock;
$most_popular_item_image = $popular_item['image'] ?? 'default.jpg';

// Retrieve the most unpopular item based on available stock (pStock)
$unpopular_item_sql = "SELECT prodID, pName, pStock, image FROM product ORDER BY pStock DESC LIMIT 1";
$unpopular_item_stmt = $conn->query($unpopular_item_sql);
$unpopular_item = $unpopular_item_stmt->fetch(PDO::FETCH_ASSOC);
$most_unpopular_item = $unpopular_item['pName'] ?? 'N/A';
$most_unpopular_item_stock = $unpopular_item['pStock'] ?? 0;
$most_unpopular_item_sold = 1000 - $most_unpopular_item_stock;
$most_unpopular_item_image = $unpopular_item['image'] ?? 'default.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            font-size: 1.1rem;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        .table thead th {
            background-color: #343a40;
            color: white;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 20px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 50px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            width: 80vw;
            max-width: 800px;
        }
        .item-img {
            max-width: 100px;
            max-height: 100px;
        }
         .logo-container {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Managerial Reports</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="Audittrail.php">Audit Trail</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin.php">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sales.php">Sales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="ResRec.php">Appointment Records</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manager.php">Managerial Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="stocks.php">Stock Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="sign.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <<div class="container mt-5 mb-5">
    <div class="logo-container">
        <img src="positif.jpg" alt="Logo" style="width: 100px; height: auto;">
    </div>
    <h1>Managerial Reports</h1>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-6">
                <h2 class="mt-4 mb-3">Monthly Sales</h2>
                <div class="chart-container">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h2 class="mt-4 mb-3">Overall Sales</h2>
                <p>Total Sales: $<?php echo number_format($overall_sales, 2); ?></p>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-6">
                <h2 class="mt-4 mb-3">Most Popular Item</h2>
                <?php if ($most_popular_item): ?>
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($most_popular_item_image); ?>" alt="Most Popular Item" class="item-img mr-3">
                        <p><?php echo htmlspecialchars($most_popular_item); ?></p>
                    </div>
                    <p>Items Sold: <?php echo $most_popular_item_sold; ?></p>
                <?php else: ?>
                    <p>No data available</p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <h2 class="mt-4 mb-3">Least Popular Item</h2>
                <?php if ($most_unpopular_item): ?>
                    <div class="d-flex align-items-center">
                        <img src="<?php echo htmlspecialchars($most_unpopular_item_image); ?>" alt="Least Popular Item" class="item-img mr-3">
                        <p><?php echo htmlspecialchars($most_unpopular_item); ?></p>
                    </div>
                    <p>Items Sold: <?php echo $most_unpopular_item_sold; ?></p>
                <?php else: ?>
                    <p>No data available</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-6">
                <h2 class="mt-4 mb-3">Appointment</h2>
                <div class="chart-container">
                    <canvas id="monthlyReservationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        &copy; 2024 Positif. All rights reserved.
    </div>

    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script>
        // Chart.js code to create the bar chart for monthly sales
        var ctxSales = document.getElementById('monthlySalesChart').getContext('2d');
        var monthlySalesChart = new Chart(ctxSales, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Monthly Sales ($)',
                    data: <?php echo json_encode(array_values($sales_by_month)); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Chart.js code to create the bar chart for monthly reservations
        var ctxReservations = document.getElementById('monthlyReservationsChart').getContext('2d');
        var monthlyReservationsChart = new Chart(ctxReservations, {
            type: 'bar',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Monthly Appointments',
                    data: <?php echo json_encode(array_values($reservations_by_month)); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
