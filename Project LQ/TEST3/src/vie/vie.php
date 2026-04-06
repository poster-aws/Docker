<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$allowedGnRanges = [10, 20, 50, 100, 365];
$vieGnCountRange = (isset($_GET['count_range']) && in_array((int) $_GET['count_range'], $allowedGnRanges, true))
    ? (int) $_GET['count_range']
    : 50;

$vieCount = 0;
$vieRows = [];
$gnDaysStats = array_fill(1, 7, null);
$gnFreqStats = array_fill(1, 7, 0);

$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $vieConn->query('SELECT COUNT(*) AS total FROM Vie');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $vieCount = (int) $row['total'];
    }
    $sql = 'SELECT Tirage, n1, n2, n3, n4, n5, GN FROM Vie ORDER BY Tirage DESC LIMIT 365';
    $res = $vieConn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $vieRows[] = $r;
        }
    }

    $sqlLastGn = 'SELECT GN, MAX(Tirage) AS Last_Tirage FROM Vie WHERE GN >= 1 AND GN <= 7 GROUP BY GN';
    $resLastGn = $vieConn->query($sqlLastGn);
    if ($resLastGn && $resLastGn->num_rows > 0) {
        $today = new DateTime();
        while ($r = $resLastGn->fetch_assoc()) {
            $g = (int) $r['GN'];
            if ($g >= 1 && $g <= 7) {
                $gnDaysStats[$g] = (new DateTime($r['Last_Tirage']))->diff($today)->days;
            }
        }
    }

    $n = $vieGnCountRange;
    $sqlFreq = "SELECT GN, COUNT(*) AS cnt FROM (SELECT GN FROM Vie ORDER BY Tirage DESC LIMIT {$n}) AS t WHERE GN >= 1 AND GN <= 7 GROUP BY GN";
    $resFreq = $vieConn->query($sqlFreq);
    if ($resFreq && $resFreq->num_rows > 0) {
        while ($r = $resFreq->fetch_assoc()) {
            $g = (int) $r['GN'];
            if ($g >= 1 && $g <= 7) {
                $gnFreqStats[$g] = (int) $r['cnt'];
            }
        }
    }
}
$vieConn->close();

include __DIR__ . '/vie.html';
