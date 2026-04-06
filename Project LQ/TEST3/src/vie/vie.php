<?php
require_once __DIR__ . '/db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

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
include __DIR__ . '/vie.html';
echo ob_get_clean();
