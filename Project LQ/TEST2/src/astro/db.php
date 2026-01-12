<?php
// === db.php ===
// Подключение к базе данных для всех страниц Astro

$servername = "db";       // Имя сервера (например: "localhost", "127.0.0.1", или docker-сервис "db")
$username   = "user";     // Имя пользователя MySQL
$password   = "user";     // Пароль пользователя
$dbname     = "astro";  // Название базы данных

// Включение отображения ошибок (на время разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение
$astroConn = new mysqli($servername, $username, $password, $dbname);

// Установка кодировки
$astroConn->set_charset("utf8");

// Проверка соединения
if ($astroConn->connect_error) {
    die("Ошибка подключения: " . $astroConn->connect_error);
}
?>