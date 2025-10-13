<?php
$servername = "localhost";
$username = "gaurav";
$password = "";
$dbname = "event_management";

$conn = new mysqli($servername, $username, $password, $dbname);//predefined class
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
