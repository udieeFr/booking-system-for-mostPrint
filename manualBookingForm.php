<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manual Booking - Step 1</title>
  <style>
    body { font-family: Arial; padding: 30px; max-width: 500px; margin: auto; background: #f4f4f4; }
    h2, label { text-align: center; display: block; margin-top: 20px; }
    input[type="file"], select, button, input[type="text"], input[type="tel"] {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .checkbox-label {
      margin-top: 15px;
      text-align: center;
      font-weight: bold;
    }
    button {
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #007BFF;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    button:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>

<h2>Step 1: Enter Details</h2>

<form action="confirmManualOrder.php" method="post" enctype="multipart/form-data">
  <label for="customerName">Customer Name:</label>
  <input type="text" name="customerName" id="customerName" required>

  <label for="phoneNumber">Phone Number:</label>
  <input type="tel" name="phoneNumber" id="phoneNumber" placeholder="e.g. 0123456789" required>

  <label for="serviceID">Select Service:</label>
  <select name="serviceID" id="serviceID" required>
    <option value="">-- Choose a Service --</option>
    <option value="S001">Printing</option>
    <option value="S002">Binding</option>
    <option value="S003">Laminating</option>
  </select>

  <label for="printFile">Upload Print Document (PDF):</label>
  <input type="file" name="printFile" id="printFile" accept="application/pdf" required>

  <!-- Fast Queue Option -->
  <div class="checkbox-label">
    <input type="checkbox" name="queueSkip" id="queueSkip">
    <label for="queueSkip">Fast Queue (+50%)</label>
  </div>

  <div style="text-align:center;">
    <button type="submit">Next</button>
  </div>
</form>

</body>
</html>