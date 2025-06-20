<?php
// db.php — подключение к базе данных

$servername = "db"; 
$username = "user";
$password = "user";
$dbname = "quotidienne2";

$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>