<?php
$servername = "db";
$username   = "user";
$password   = "user";
$dbname     = "quotidienne";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>
