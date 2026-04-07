<?php
$servername = "db";
$username   = "user";
$password   = "user";
$dbname     = "banco";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$bancoConn = new mysqli($servername, $username, $password, $dbname);
$bancoConn->set_charset("utf8");

if ($bancoConn->connect_error) {
    die("Ошибка подключения: " . $bancoConn->connect_error);
}
?>
