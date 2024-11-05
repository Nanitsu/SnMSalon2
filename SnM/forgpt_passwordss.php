<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Forgot Password</title>
    <style>
        body {
            background-image: url('signbg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 400px;
            margin-top: 100px;
        }
        .logo {
            display: block;
            margin: 0 auto 20px auto;
        }
    </style>
</head>
<body>
    <img src="positif.jpg" alt="Logo" class="logo" style="height: 60px;">
    <div class="container">
        <h2 class="text-center">Forgot Password</h2>
        <form method="post" action="verify_reset.php">
            <div class="form-group">
                <label for="forgot_password_email">Enter your email address:</label>
                <input type="email" class="form-control" id="forgot_password_email" name="forgot_password_email" placeholder="Email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
