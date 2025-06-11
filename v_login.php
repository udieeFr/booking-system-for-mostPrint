<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Printing Service - Login</title>
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

    .login-container {
      background: rgba(219, 203, 146, 0.85);
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      width: 300px;
      backdrop-filter: blur(5px);
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-top: 10px;
    }

    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
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
    }

    button:hover {
      background-color: #45a049;
    }

    .guest-link {
      text-align: center;
      margin-top: 15px;
    }

    .guest-link a {
      color: #007BFF;
      text-decoration: none;
    }

    .guest-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <h2>Login</h2>
    <form id="loginForm" method="POST" action="login.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required />

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />

      <label for="role">Login As</label>
      <select id="role" name="role" required>
        <option value="">Select Role</option>
        <option value="staff">Staff</option>
        <option value="customer">Customer</option>
        <option value="guest">Guest</option>
      </select>

      <button type="submit">Login</button>
    </form>

    <div class="guest-link">
      <p>Don't have an account? <a href="v_register.php">Register now</a></p>
    </div>
  </div>

  <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const role = document.getElementById('role').value;

      if (!role) {
        alert("Please select a login role.");
        e.preventDefault();
      }
    });
  </script>

</body>
</html>