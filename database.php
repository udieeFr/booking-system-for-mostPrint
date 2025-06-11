<?php 

$db_server = "localhost";
$db_user = "root";
$db_pass = ""; // ← No password
$db_name = "mostdb";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// echo "Connected successfully!";

?>