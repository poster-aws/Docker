<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$vieCount = 0;
$vieRows = [];
$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $vieConn->query('SELECT COUNT(*) AS total FROM Vie');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $vieCount = (int)$row['total'];
    }
    $sql = 'SELECT Tirage, n1, n2, n3, n4, n5, GN FROM Vie ORDER BY Tirage DESC LIMIT 365';
    $res = $vieConn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $vieRows[] = $r;
        }
    }
}
$vieConn->close();

include __DIR__ . '/vie.html';
