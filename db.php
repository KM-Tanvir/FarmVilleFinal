<?php
// db.php
$servername = "localhost";
$username = "root";
$password = ""; // XAMPP default password is empty
$dbname = "FarmVille"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>