<?php
session_start();
require_once 'database.php';

$customerName = $phoneNumber = $password = "";
$customerName_err = $phoneNumber_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate Username
    if (empty(trim($_POST["username"]))) {
        $customerName_err = "Please enter a username.";
    } else {
        $customerName = trim($_POST["username"]);
    }

    // Validate Phone Number
    if (empty(trim($_POST["phoneNumber"]))) {
        $phoneNumber_err = "Please enter your phone number.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", trim($_POST["phoneNumber"]))) {
        $phoneNumber_err = "Phone number must be 10–15 digits only.";
    } else {
        $phoneNumber = trim($_POST["phoneNumber"]);
    }

    // Validate Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate Confirm Password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($password != $confirm_password) {
            $confirm_password_err = "Passwords did not match.";
        }
    }

    // Check input errors before inserting into database
    if (empty($customerName_err) && empty($phoneNumber_err) && empty($password_err) && empty($confirm_password_err)) {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO customers (CustomerName, PhoneNumber, passwordHash) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $customerName, $phoneNumber, $passwordHash);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "Registration successful!";
                header("Location: v_register.php");
                exit();
            } else {
                $_SESSION['error'] = "Database error. Please try again later.";
                header("Location: v_register.php");
                exit();
            }

            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['error'] = "Error preparing statement.";
            header("Location: v_register.php");
            exit();
        }

    } else {
        // Validation failed
        $errorList = '';
        if (!empty($customerName_err)) $errorList .= "<li>$customerName_err</li>";
        if (!empty($phoneNumber_err)) $errorList .= "<li>$phoneNumber_err</li>";
        if (!empty($password_err)) $errorList .= "<li>$password_err</li>";
        if (!empty($confirm_password_err)) $errorList .= "<li>$confirm_password_err</li>";

        $_SESSION['error'] = "<ul class='error-list'>$errorList</ul>";
        header("Location: v_register.php");
        exit();
    }

// End of POST check
}
?>