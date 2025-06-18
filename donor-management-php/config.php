<?php
// Database connection configuration
$host = 'localhost';
$dbname = 'donor_management';
$user = 'root';
$password = '';

// Create connection using PDO to avoid missing mysqli extension error
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
