<!-- src/banco/db.php -->

<?php
// === db.php ===
// Подключение к базе данных для всех страниц Tout ou Rien

$servername = "db";       // Имя сервера (например: "localhost", "127.0.0.1", или docker-сервис "db")
$username   = "user";     // Имя пользователя MySQL
$password   = "user";     // Пароль пользователя
$dbname     = "banco";  // Название базы данных

// Включение отображения ошибок (на время разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение
$bancoConn = new mysqli($servername, $username, $password, $dbname);

// Установка кодировки
$bancoConn->set_charset("utf8");

// Проверка соединения
if ($bancoConn->connect_error) {
    die("Ошибка подключения: " . $bancoConn->connect_error);
}
?>