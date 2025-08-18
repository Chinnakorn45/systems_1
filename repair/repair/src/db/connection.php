<?php
// Database configuration
$host = getenv('DB_HOST') ?: 'localhost'; // Database host
$dbname = getenv('DB_NAME') ?: 'repair_db'; // Database name
$username = getenv('DB_USERNAME') ?: 'appuser'; // Database username
$password = getenv('DB_PASSWORD') ?: ''; // Database password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error
    echo "Connection failed: " . $e->getMessage();
}
?>