<?php
$servername = 'db';
$username   = 'user';
$password   = 'user';
$dbname     = 'toutourien';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$toutConn = new mysqli($servername, $username, $password, $dbname);
$toutConn->set_charset('utf8');

if ($toutConn->connect_error) {
    die('Ошибка подключения: ' . $toutConn->connect_error);
}
