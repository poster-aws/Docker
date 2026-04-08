<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$allowedLimits = [50, 200];
$limit = isset($_GET['limit']) && in_array((int) $_GET['limit'], $allowedLimits, true)
    ? (int) $_GET['limit']
    : 50;

$bancoCount = 0;
$tirages = [];
$totals = array_fill(1, 70, 0);

$tableExists = $bancoConn->query("SHOW TABLES LIKE 'banco'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $bancoConn->query('SELECT COUNT(*) AS total FROM banco');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $bancoCount = (int) $row['total'];
    }

    $sql = 'SELECT Tirage, turbo, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12,n13,n14,n15,n16,n17,n18,n19,n20
            FROM banco
            ORDER BY Tirage DESC
            LIMIT ' . (int) $limit;
    $res = $bancoConn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($r = $res->fetch_assoc()) {
            $nums = [];
            for ($i = 1; $i <= 20; $i++) {
                $n = (int) ($r['n' . $i] ?? 0);
                $nums[] = $n;
                if ($n >= 1 && $n <= 70) {
                    $totals[$n]++;
                }
            }
            $tirages[] = [
                'Tirage' => (string) $r['Tirage'],
                'turbo'  => (int) ($r['turbo'] ?? 0),
                'flip'   => array_flip($nums),
            ];
        }
    }
}

$bancoConn->close();

include __DIR__ . '/banco.html';
