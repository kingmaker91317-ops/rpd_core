<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Security Validation</title>

  <!-- AUTO REDIRECT AFTER 5 SECONDS -->
  <meta http-equiv="refresh" content="5;url=<?= base_url('login') ?>">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', system-ui, sans-serif;
    }

    body {
      background: linear-gradient(135deg, #1a2a6c, #2a4d69, #4b86b4);
      color: white;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 20px;
    }

    .container {
      max-width: 600px;
      background: rgba(0, 0, 0, 0.25);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    h1 {
      font-size: 2.2rem;
      margin-bottom: 16px;
      font-weight: 600;
    }

    p {
      font-size: 1.1rem;
      opacity: 0.9;
      line-height: 1.6;
    }

    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top: 4px solid white;
      border-radius: 50%;
      margin: 30px auto;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    .footer {
      margin-top: 30px;
      font-size: 0.85rem;
      opacity: 0.7;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>SECURITY VALIDATION</h1>
    <div class="spinner"></div>
    <p>Checking browser...</p>
    <div class="footer">Please wait while we verify your session.</div>
  </div>
</body>
</html>
