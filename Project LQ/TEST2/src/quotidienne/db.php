<?php
// === db.php ===
// Подключение к базе данных для всех страниц Quotidienne

$servername = "db";       // Имя сервера (например: "localhost", "127.0.0.1", или docker-сервис "db")
$username   = "user";     // Имя пользователя MySQL
$password   = "user";     // Пароль пользователя
$dbname     = "quotidienne";  // Название базы данных

// Включение отображения ошибок (на время разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Установка кодировки
$conn->set_charset("utf8");

// Проверка соединения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
?>