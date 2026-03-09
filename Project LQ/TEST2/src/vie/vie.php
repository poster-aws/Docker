<?php
require_once "db.php";

$vieCount = 0;
$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $vieConn->query("SELECT COUNT(*) AS total FROM Vie");
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $vieCount = (int)$row['total'];
    }
}
$vieConn->close();

ob_start();
include 'vie.html';
echo ob_get_clean();
