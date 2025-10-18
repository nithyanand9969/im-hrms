<?php
// Database configuration
$servername = "localhost";
$username = "pashupra_sanjivini";
$password = "Mumbai@2050";
$dbname = "pashupra_sanjivini";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
