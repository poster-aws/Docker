<?php
$servername = 'db';
$username   = 'user';
$password   = 'user';
$dbname     = 'vie';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$vieConn = new mysqli($servername, $username, $password, $dbname);
$vieConn->set_charset('utf8');

if ($vieConn->connect_error) {
    die('Ошибка подключения: ' . $vieConn->connect_error);
}
