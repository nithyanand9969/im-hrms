<?php
// Database configuration
$servername = "localhost";
$username = "pashupra_employees";
$password = "Mumbai@2050";
$dbname = "pashupra_employees";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
