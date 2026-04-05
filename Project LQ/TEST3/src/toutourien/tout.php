<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$countResult = $toutConn->query('SELECT COUNT(*) AS total FROM Tout');
$toutCount = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $toutCount = (int)$row['total'];
}

$allowedLimits = [50, 200];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits, true)
    ? (int)$_GET['limit']
    : 50;

$sql = 'SELECT Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12
        FROM Tout
        ORDER BY Tirage DESC
        LIMIT ' . (int)$limit;
$res = $toutConn->query($sql);

$tirages = [];
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $nums = array_map('intval', [
            $r['n1'], $r['n2'], $r['n3'], $r['n4'], $r['n5'], $r['n6'],
            $r['n7'], $r['n8'], $r['n9'], $r['n10'], $r['n11'], $r['n12'],
        ]);
        $tirages[] = [
            'Tirage' => $r['Tirage'],
            'nums'   => $nums,
            'flip'   => array_flip($nums),
        ];
    }
}

$totals = array_fill(1, 24, 0);
foreach ($tirages as $t) {
    foreach ($t['nums'] as $num) {
        if ($num >= 1 && $num <= 24) {
            $totals[$num]++;
        }
    }
}

$toutConn->close();

include __DIR__ . '/tout.html';
