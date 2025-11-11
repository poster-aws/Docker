<?php
// === db.php ===
// Подключение к базе данных для всех страниц Tout ou Rien

$servername = "db";       // Имя сервера (например: "localhost", "127.0.0.1", или docker-сервис "db")
$username   = "user";     // Имя пользователя MySQL
$password   = "user";     // Пароль пользователя
$dbname     = "toutourien";  // Название базы данных

// Включение отображения ошибок (на время разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Подключение
$toutConn = new mysqli($servername, $username, $password, $dbname);

// Установка кодировки
$toutConn->set_charset("utf8");

// Проверка соединения
if ($toutConn->connect_error) {
    die("Ошибка подключения: " . $toutConn->connect_error);
}
?>