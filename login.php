<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $role = trim($_POST["role"]);

    // Validate inputs
    if (empty($username) || empty($password) || empty($role)) {
        header("Location: v_login.php?error=empty_fields");
        exit();
    }

    // Determine user type and set appropriate queries
    if ($role === 'customer') {
        $sql = "SELECT CustomerID, CustomerName, passwordHash FROM customers WHERE CustomerName = ?";
        $redirect = "custHome.php";
        $id_field = 'CustomerID';
        $name_field = 'CustomerName';
    } 
    elseif ($role === 'staff') {
        $sql = "SELECT StaffID, StaffName, passwordHash FROM staffs WHERE StaffName = ?";
        $redirect = "staffHome.php";
        $id_field = 'StaffID';
        $name_field = 'StaffName';
    } 
    else {
        header("Location: v_login.php?error=invalid_role");
        exit();
    }

    // Prepare and execute SQL statement
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        header("Location: v_login.php?error=db_error");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if user exists
    if ($user = mysqli_fetch_assoc($result)) {
        // Verify password
        if (password_verify($password, $user['passwordHash'])) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['user_type'] = $role;
            $_SESSION['role'] = $role; // Added for compatibility
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['user_name'] = $user[$name_field];
            $_SESSION['username'] = $user[$name_field]; // Added for staffHome.php

            // Redirect to appropriate dashboard
            header("Location: $redirect");
            exit();
        } else {
            header("Location: v_login.php?error=invalid_password");
            exit();
        }
    } else {
        header("Location: v_login.php?error=user_not_found");
        exit();
    }
}

mysqli_close($conn);
?>