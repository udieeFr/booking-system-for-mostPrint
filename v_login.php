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

    .register-link {
      text-align: center;
      margin-top: 15px;
    }

    .register-link a {
      color: #007BFF;
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    .error-message {
      color: #dc3545;
      text-align: center;
      margin-top: 10px;
      font-size: 14px;
    }

    /* Toast-style notification */
    .toast {
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #333;
      color: #fff;
      padding: 12px 20px;
      border-radius: 5px;
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.5s ease;
      pointer-events: none;
      min-width: 250px;
      text-align: center;
    }

    .toast.show {
      opacity: 1;
    }

    .toast.success {
      background-color: #28a745;
    }

    .toast.error {
      background-color: #dc3545;
    }
  </style>
</head>
<body>

  <!-- Toast Notification -->
  <div id="toast" class="toast"></div>

  <div class="login-container">
    <h2>Login</h2>
    <?php
    if (isset($_GET['error'])) {
        $error = htmlspecialchars($_GET['error']);
        $messages = [
            'empty_fields' => 'Please fill all fields',
            'invalid_role' => 'Invalid role selected',
            'db_error' => 'Database error occurred',
            'invalid_password' => 'Invalid password',
            'user_not_found' => 'User not found'
        ];
        echo '<div class="error-message">' . ($messages[$error] ?? 'Login failed') . '</div>';
    }
    ?>
    <form id="loginForm" method="POST" action="login.php">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" required placeholder="Enter your username" />

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required />

      <label for="role">Login As</label>
      <select id="role" name="role" required>
        <option value="">Select Role</option>
        <option value="customer" selected>Customer</option>
        <option value="staff">Staff</option>
      </select>

      <button type="submit">Login</button>
    </form>

    <div class="register-link">
      <p>Don't have an account? <a href="v_register.php">Register now</a></p>
    </div>
  </div>

  <script>
    // Toast notification function
    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.className = 'toast ' + type;
      toast.classList.add('show');

      setTimeout(() => {
        toast.classList.remove('show');
      }, 3000);
    }

    // Handle form submission
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const role = document.getElementById('role').value;

      if (!role || role === '') {
        e.preventDefault();
        showToast("Please select a login role.", "error");
      }
    });

    // Show error messages from URL
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const errorMessages = {
        'empty_fields': 'Please fill all fields',
        'invalid_role': 'Invalid role selected',
        'db_error': 'Database error occurred',
        'invalid_password': 'Invalid password',
        'user_not_found': 'User not found'
      };

      if (urlParams.has('error')) {
        const error = urlParams.get('error');
        if (errorMessages[error]) {
          showToast(errorMessages[error], "error");
        }
      }
    });
  </script>
</body>
</html>