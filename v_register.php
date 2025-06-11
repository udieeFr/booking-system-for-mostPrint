<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Printing Service - Register</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: url('img/background.jpg');
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .register-container {
      background: rgba(255, 250, 230, 0.9);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      width: 320px;
      backdrop-filter: blur(5px);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #333;
    }

    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    input:focus {
      outline: none;
      border-color: #4CAF50;
      box-shadow: 0 0 3px rgba(76, 175, 80, 0.5);
    }

    button {
      margin-top: 20px;
      width: 100%;
      padding: 10px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      font-size: 16px;
    }

    button:hover {
      background-color: #45a049;
    }

    .back-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .back-link a {
      color: #007BFF;
      text-decoration: none;
    }

    .back-link a:hover {
      text-decoration: underline;
    }

    .success-message,
    .error-message {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      padding: 12px 20px;
      border-radius: 5px;
      z-index: 1000;
      animation: fadeOut 3s ease forwards;
    }

    .success-message {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .error-message {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    @keyframes fadeOut {
      0% { opacity: 1; }
      90% { opacity: 1; }
      100% { opacity: 0; visibility: hidden; }
    }

    ul.error-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    ul.error-list li {
      background: #ffe6e6;
      border-left: 4px solid red;
      padding: 6px 10px;
      margin-bottom: 10px;
      color: #b30000;
      font-size: 14px;
    }
  </style>
</head>
<body>

<?php if (!empty($_SESSION['success'])): ?>
  <div class="success-message"><?= $_SESSION['success'] ?></div>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
  <div class="error-message"><?= $_SESSION['error'] ?></div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="register-container">
  <h2>Register</h2>
  <form id="registerForm" method="POST" action="register.php">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required />

    <label for="phoneNumber">Phone Number</label>
    <input type="text" id="phoneNumber" name="phoneNumber" required />

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required />

    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required />

    <button type="submit">Register</button>
  </form>

  <div class="back-link">
    <p>Already have an account? <a href="v_login.php">Login here</a></p>
  </div>
</div>

<script>
  document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password !== confirmPassword) {
      e.preventDefault();
      alert("Passwords do not match.");
    }
  });
</script>

</body>
</html>