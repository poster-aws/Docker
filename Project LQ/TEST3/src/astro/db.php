<?php
$servername = "db";
$username   = "user";
$password   = "user";
$dbname     = "astro";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$astroConn = new mysqli($servername, $username, $password, $dbname);
$astroConn->set_charset("utf8");

if ($astroConn->connect_error) {
    die("Ошибка подключения: " . $astroConn->connect_error);
}
?>
