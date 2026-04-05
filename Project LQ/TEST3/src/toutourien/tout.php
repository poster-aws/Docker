<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tout_verify'])) {
    $_GET['lang'] = (isset($_POST['lang']) && $_POST['lang'] === 'en') ? 'en' : 'fr';
}

require_once __DIR__ . '/../i18n.php';

$ALL_BUCKETS = [0, 1, 2, 3, 4, 8, 9, 10, 11, 12];

function tout_verifier_validate_numbers(array $nums, &$errMsg): bool
{
    if (count($nums) !== 12) {
        $errMsg = t('verifier.validate_12');
        return false;
    }
    $uniq = array_unique($nums);
    if (count($uniq) !== 12) {
        $errMsg = t('verifier.validate_unique');
        return false;
    }
    foreach ($uniq as $n) {
        if (!is_int($n) || $n < 1 || $n > 24) {
            $errMsg = t('verifier.validate_range');
            return false;
        }
    }
    return true;
}

function tout_verifier_prize_for_matches(int $k): ?string
{
    $map = [
        0  => '250 000$',
        1  => '1 000$',
        2  => '25$',
        3  => '10$',
        4  => '2$',
        8  => '2$',
        9  => '10$',
        10 => '25$',
        11 => '1 000$',
        12 => '250 000$',
    ];
    return $map[$k] ?? null;
}

function tout_verifier_build_table_rows(array $distributionRows): array
{
    global $ALL_BUCKETS;

    $byK = [];
    foreach ($distributionRows as $r) {
        $byK[(int)$r['matches']] = (int)$r['cnt'];
    }
    $rows = [];
    foreach ($ALL_BUCKETS as $k) {
        $rows[] = [
            'k'   => $k,
            'cnt' => array_key_exists($k, $byK) ? $byK[$k] : null,
            'pr'  => tout_verifier_prize_for_matches($k),
        ];
    }
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tout_verify'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $raw = isset($_POST['numbers']) ? (string)$_POST['numbers'] : '';
    $parts = preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
    $numbers = array_map('intval', $parts);

    if (!tout_verifier_validate_numbers($numbers, $errMsg)) {
        echo json_encode([
            'ok'            => false,
            'resultMessage' => $errMsg,
            'resultColor'   => 'red',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $safeList = implode(',', array_map('intval', $numbers));

    $sqlExact = "
        SELECT 1
        FROM Tout
        WHERE ((n1  IN ($safeList)) + (n2  IN ($safeList)) + (n3  IN ($safeList)) + (n4  IN ($safeList)) +
               (n5  IN ($safeList)) + (n6  IN ($safeList)) + (n7  IN ($safeList)) + (n8  IN ($safeList)) +
               (n9  IN ($safeList)) + (n10 IN ($safeList)) + (n11 IN ($safeList)) + (n12 IN ($safeList))) = 12
        LIMIT 1
    ";
    $resExact = $toutConn->query($sqlExact);

    if (!$resExact) {
        $toutConn->close();
        echo json_encode([
            'ok'            => false,
            'resultMessage' => t('verifier.db_error'),
            'resultColor'   => 'red',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($resExact->num_rows > 0) {
        $resultMessage = t('verifier.found');
        $resultColor = 'green';
    } else {
        $resultMessage = t('verifier.not_found');
        $resultColor = 'orange';
    }

    $distributionSql = "
      SELECT
        b.k AS matches,
        COALESCE(a.draws_count, 0) AS cnt
      FROM
        ( SELECT 0 AS k UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
          UNION ALL SELECT 4 UNION ALL SELECT 8 UNION ALL SELECT 9
          UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
        ) b
      LEFT JOIN (
        SELECT matches, COUNT(*) AS draws_count
        FROM (
          SELECT (
            (n1  IN ($safeList)) +
            (n2  IN ($safeList)) +
            (n3  IN ($safeList)) +
            (n4  IN ($safeList)) +
            (n5  IN ($safeList)) +
            (n6  IN ($safeList)) +
            (n7  IN ($safeList)) +
            (n8  IN ($safeList)) +
            (n9  IN ($safeList)) +
            (n10 IN ($safeList)) +
            (n11 IN ($safeList)) +
            (n12 IN ($safeList))
          ) AS matches
          FROM Tout
        ) mm
        GROUP BY matches
      ) a
      ON a.matches = b.k
      ORDER BY b.k
    ";

    $distributionRows = [];
    if ($resDist = $toutConn->query($distributionSql)) {
        while ($row = $resDist->fetch_assoc()) {
            $distributionRows[] = [
                'matches' => (int)$row['matches'],
                'cnt'     => (int)$row['cnt'],
            ];
        }
        $resDist->free();
    }

    $toutConn->close();

    $tableRows = tout_verifier_build_table_rows($distributionRows);

    echo json_encode([
        'ok'            => true,
        'resultMessage' => $resultMessage,
        'resultColor'   => $resultColor,
        'tableRows'     => $tableRows,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

$verifierPlaceholderRows = tout_verifier_build_table_rows([]);

include __DIR__ . '/tout.html';
