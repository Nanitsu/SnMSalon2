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
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: sign.php");
    exit();
}

// Fetch payments from the payment table
$sql_payment = "SELECT * FROM payment";
$stmt_payment = $conn->prepare($sql_payment);
$stmt_payment->execute();
$result_payment = $stmt_payment->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Scissors and Mirrors Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Styles reused from previous design */
        html, body { height: 100%; margin: 0; font-family: 'Outfit', sans-serif; background-color: #fff; color: #333; }
        .app-bar { background-color: #002f6c; padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; color: #fff; }
        .title { font-size: 32px; color: #f0c27b; font-family: 'Playfair Display', serif; font-weight: bold; }
        .table thead th { background-color: #002f6c; color: #f0c27b; font-family: 'Outfit', sans-serif; }
        .table tbody tr:nth-of-type(odd) { background-color: #e6f0ff; }
        .btn-primary { background-color: #f0c27b; color: #002f6c; font-family: 'Outfit', sans-serif; }
        .footer { background-color: #002f6c; color: #fff; padding: 20px; text-align: center; flex-shrink: 0; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="app-bar">
        <img src="SnM.jpg" alt="Salon Logo" class="logo">
        <div class="title">Payments - Scissors and Mirrors Salon</div>
        <form method="POST" style="margin-left: auto;">
            <button type="submit" name="logout" class="btn btn-danger">Logout</button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper container mt-5 mb-5">
        <h2 class="text-center" style="color: #002f6c;">Payments</h2>
        <hr>

        <!-- Display Payments from the "payment" table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>pID</th>
                        <th>pAmount</th>
                        <th>pType</th>
                        <th>uID</th>
                        <th>pTime</th>
                        <th>aID</th>
                        <th>nID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result_payment as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pID']) ?></td>
                        <td><?= htmlspecialchars($row['pAmount']) ?></td>
                        <td><?= htmlspecialchars($row['pType']) ?></td>
                        <td><?= htmlspecialchars($row['uID']) ?></td>
                        <td><?= htmlspecialchars($row['pTime']) ?></td>
                        <td><?= htmlspecialchars($row['aID']) ?></td>
                        <td><?= htmlspecialchars($row['nID']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 Scissors and Mirrors Salon</p>
    </footer>
</body>
</html>
