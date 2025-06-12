<?php
// Verify CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line");
}

// 1. Load database connection from your existing file
require_once 'database.php';

// 2. Verify master password (standalone in config.ini)
$configPath = __DIR__ . '/config.ini';
if (!file_exists($configPath)) {
    die("config.ini file not found in script directory");
}

$config = parse_ini_file($configPath);
if (!isset($config['master_password_hash'])) {
    die("Master password not configured in config.ini");
}

// 3. Verify master password
echo "Master Password: ";
$masterInput = trim(fgets(STDIN));
if (!password_verify($masterInput, $config['master_password_hash'])) {
    die("Invalid master password\n");
}

// 4. Get new admin details
echo "\nEnter new admin details:\n";

echo "Staff ID: ";
$staffID = trim(fgets(STDIN));

echo "Staff Name: ";
$staffName = trim(fgets(STDIN));

echo "Password: ";
$password = trim(fgets(STDIN));

echo "Confirm Password: ";
$confirm = trim(fgets(STDIN));

// Validate input
if ($password !== $confirm) {
    die("Error: Passwords do not match\n");
}

if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters\n");
}

// 5. Create admin account using existing database connection
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO staffs (StaffID, StaffName, passwordHash) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $staffID, $staffName, $hashedPassword);

if ($stmt->execute()) {
    echo "\nSuccess! Staff account created:\n";
    echo "Staff ID: $staffID\n";
    echo "Staff Name: $staffName\n";
} else {
    echo "\nError creating account: " . $conn->error . "\n";
}

$stmt->close();
// Don't close $conn if it's used elsewhere in your application
?>