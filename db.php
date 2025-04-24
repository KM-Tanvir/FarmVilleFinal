<?php
// Database connection parameters
$servername = "localhost";
$username = "root";         
$password = "";             
$dbname = "farmville";    

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection Failed". $conn->connect_error);
}
else {
    echo "<script>console.log('Database Connected Successfully');</script>";
}