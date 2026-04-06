<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* Fenêtre stats GN : nombre de tirages (dates Tirage distinctes), pas des jours. */
$allowedGnTirages = [10, 20, 50, 100, 200];
$rawTirages = $_GET['vie_gn_tirages'] ?? $_GET['vie_gn_range'] ?? null;
$vieGnTirageCount = ($rawTirages !== null && in_array((int) $rawTirages, $allowedGnTirages, true))
    ? (int) $rawTirages
    : 50;

$vieCount = 0;
$vieRows = [];
/* Colonne 2 : tirages passés = nb de dates distinctes (GN >= 1) strictement après la dernière sortie du GN.
   Lignes GN = 0 : ne définissent pas un tirage pour ces stats. */
$gnTiragesPasses = array_fill(1, 7, null);
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

    for ($g = 1; $g <= 7; $g++) {
        $resLast = $vieConn->query('SELECT MAX(Tirage) AS m FROM Vie WHERE GN = ' . (int) $g);
        if ($resLast && ($rowLast = $resLast->fetch_assoc()) && $rowLast['m'] !== null && $rowLast['m'] !== '') {
            $lastT = $vieConn->real_escape_string((string) $rowLast['m']);
            $sqlPass = "SELECT COUNT(DISTINCT Tirage) AS c FROM Vie WHERE GN >= 1 AND GN <= 7 AND Tirage > '{$lastT}'";
            $resPass = $vieConn->query($sqlPass);
            if ($resPass && ($rowPass = $resPass->fetch_assoc())) {
                $gnTiragesPasses[$g] = (int) $rowPass['c'];
            } else {
                $gnTiragesPasses[$g] = 0;
            }
        }
    }

    /* Fréquence GN : n derniers tirages = n dates distinctes avec GN >= 1 (GN = 0 ignoré). */
    $n = (int) $vieGnTirageCount;
    $sqlFreq = "SELECT v.GN, COUNT(*) AS cnt
        FROM Vie v
        INNER JOIN (
            SELECT Tirage FROM Vie WHERE GN >= 1 AND GN <= 7 GROUP BY Tirage ORDER BY Tirage DESC LIMIT {$n}
        ) AS recent ON v.Tirage = recent.Tirage
        WHERE v.GN >= 1 AND v.GN <= 7
        GROUP BY v.GN";
    $resFreq = $vieConn->query($sqlFreq);
    if ($resFreq && $resFreq->num_rows > 0) {
        while ($r = $resFreq->fetch_assoc()) {
            $g = (int) ($r['GN'] ?? 0);
            if ($g >= 1 && $g <= 7) {
                $gnFreqStats[$g] = (int) ($r['cnt'] ?? 0);
            }
        }
    }
}
$vieConn->close();

include __DIR__ . '/vie.html';
