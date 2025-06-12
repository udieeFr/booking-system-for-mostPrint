<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: v_login.php?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Order - Step 1</title>
  <style>
    body { font-family: Arial; padding: 30px; max-width: 500px; margin: auto; background: #f4f4f4; }
    h2, label { text-align: center; display: block; margin-top: 20px; }
    select, button { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
  </style>
</head>
<body>

<h2>Step 1: Select Service & Upload File</h2>

<form action="confirmOrder.php" method="post" enctype="multipart/form-data">
  <label for="serviceID">Select Service:</label>
  <select name="serviceID" id="serviceID" required onchange="updatePrice(this.value)">
    <option value="">-- Choose a Service --</option>
    <option value="S001">Printing (RM0.10/page)</option>
    <option value="S002">Binding (RM0.05/page)</option>
    <option value="S003">Laminating (RM0.50/page)</option>
  </select>

  <label for="printFile">Upload Print Document (PDF):</label>
  <input type="file" name="printFile" id="printFile" accept="application/pdf" required>

  <div style="text-align:center; margin-top: 20px;">
    <button type="submit">Next</button>
  </div>
</form>

</body>
</html>