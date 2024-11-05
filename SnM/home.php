<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Positif Salon</title>
  <style>
    body {
      margin: 0;
      font-family: 'Outfit', sans-serif;
      background-color: #e1e5ea;
    }
    .app-bar {
      background-color: #002f6c;
      padding: 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .title {
      font-size: 26px;
      color: #f0c27b; /* Gold color */
      font-family: 'Playfair Display', serif;
      font-weight: bold;
    }
    .button {
      background-color: #f0c27b; /* Gold color */
      color: #002f6c;
      border: none;
      border-radius: 24px;
      padding: 12px 24px;
      font-size: 16px;
      font-family: 'Outfit', sans-serif;
      cursor: pointer;
      margin: 0 4px;
      transition: background-color 0.3s ease;
    }
    .button:hover {
      background-color: #d9a83f; /* Darker gold on hover */
    }
    .button:focus {
      outline: none;
    }
    .container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: calc(100vh - 72px);
      position: relative;
      overflow: hidden;
    }
    .image1 {
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      border-radius: 8px;
      width: 471px;
      height: 100%;
      object-fit: cover;
      filter: brightness(80%);
    }
    .image2 {
      position: absolute;
      top: 16px;
      left: 16px;
      border-radius: 8px;
      width: 279px;
      height: 165px;
      object-fit: cover;
      filter: brightness(80%);
    }
    .welcome-text {
      position: absolute;
      top: 45%;
      left: 40%;
      transform: translate(-50%, -50%);
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      color: #f0c27b; /* Gold color */
      text-align: center;
      margin: 0;
    }
    .revolution-text {
      position: absolute;
      top: 55%;
      left: 40%;
      transform: translate(-50%, -50%);
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      color: #ffffff;
      text-align: center;
      margin: 0;
    }
  </style>
</head>
<body>
  <div class="app-bar">
    <div class="title">Positif Salon</div>
    <div>
      <button class="button" onclick="window.location.href='sign.php'">Login</button>
      <button class="button" onclick="window.location.href='index.php'">Register</button>
    </div>
  </div>
  <div class="container">
    <img src="https://images.unsplash.com/photo-1563391017873-6e6beab67fed?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0NTYyMDF8MHwxfHNlYXJjaHwxNXx8Y29uc3RydWN0aW9ufGVufDB8fHx8MTcxODk0OTI3M3ww&ixlib=rb-4.0.3&q=80&w=1080" alt="Construction" class="image1">
    <img src="https://storage.googleapis.com/flutterflow-io-6f20.appspot.com/projects/home-ihhya8/assets/2vxqqes66cs0/positif.png" alt="Positif Logo" class="image2">
    <div class="welcome-text">Welcome to Positif Salon!</div>
    <div class="revolution-text">Revitalizing Your Style</div>
  </div>
</body>
</html>
