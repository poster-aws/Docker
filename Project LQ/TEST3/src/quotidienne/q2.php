<?php
require_once "db.php";

$countQuery = "SELECT COUNT(*) as total FROM Q2";
$countResult = $conn->query($countQuery);
$q2count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q2count = (int)$row['total'];
}

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$tableMain  = $isNorder ? 'Q2_stats_norder' : 'Q2_stats_order';
$tableCombOrder  = 'Q2_combo_stats_order';
$tableCombNorder = 'Q2_combo_stats_norder';

$allowedRanges = [10, 20, 50, 100, 365];
$countRange = (isset($_GET['count_range']) && in_array((int)$_GET['count_range'], $allowedRanges, true))
    ? (int)$_GET['count_range']
    : 50;

$sql = "SELECT * FROM $tableMain ORDER BY Tirage DESC LIMIT 365";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$comboRows = [];
if ($isNorder) {
    $sqlCombo = "SELECT n1, n2, days, Tirage, max_fois, max_days FROM Q2_combo_stats_norder ORDER BY days DESC";
} else {
    $sqlCombo = "SELECT n1, n2, days, Tirage, max_fois, max_days FROM Q2_combo_stats_order ORDER BY days DESC";
}
$resCombo = $conn->query($sqlCombo);
if ($resCombo && $resCombo->num_rows > 0) {
    while ($r = $resCombo->fetch_assoc()) {
        $comboRows[] = [
            'n1' => $r['n1'], 'n2' => $r['n2'], 'days' => $r['days'],
            'date' => $r['Tirage'], 'max_fois' => $r['max_fois'] ?? '-', 'max_days' => $r['max_days'] ?? '-'
        ];
    }
}

$daysStats = array_fill(0, 10, null);
$sqlLastNums = "SELECT n, MAX(Tirage) AS Last_Tirage FROM (SELECT n1 AS n, Tirage FROM Q2_stats_order UNION ALL SELECT n2 AS n, Tirage FROM Q2_stats_order) AS AllNums GROUP BY n";
$resLastNums = $conn->query($sqlLastNums);
if ($resLastNums && $resLastNums->num_rows > 0) {
    while ($r = $resLastNums->fetch_assoc()) {
        $days = (new DateTime($r['Last_Tirage']))->diff(new DateTime())->days;
        $idx = (int)$r['n'];
        if ($idx >= 0 && $idx <= 9) $daysStats[$idx] = $days;
    }
}

$freqStats = array_fill(0, 10, 0);
$sqlFreq = "SELECT num AS digit, COUNT(*) AS cnt FROM (SELECT n1 AS num FROM (SELECT n1, n2 FROM Q2 ORDER BY Tirage DESC LIMIT $countRange) AS t1 UNION ALL SELECT n2 AS num FROM (SELECT n1, n2 FROM Q2 ORDER BY Tirage DESC LIMIT $countRange) AS t2) AS allnums GROUP BY num";
$resFreq = $conn->query($sqlFreq);
if ($resFreq && $resFreq->num_rows > 0) {
    while ($r = $resFreq->fetch_assoc()) {
        $digit = (int)$r['digit'];
        if ($digit >= 0 && $digit <= 9) $freqStats[$digit] = (int)$r['cnt'];
    }
}

$conn->close();

ob_start();
include 'q2.html';
$template = ob_get_clean();

$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach ($row as $key => $cell) {
        if ($key === 'n1' || $key === 'n2') {
            $tableHTML .= '<td><span class="circle">' . htmlspecialchars($cell) . '</span></td>';
        } else {
            $tableHTML .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
    }
    $tableHTML .= '</tr>';
}

$comboHTML = '';
foreach ($comboRows as $row) {
    $comboHTML .= '<tr><td><span class="circle">' . $row['n1'] . '</span></td><td><span class="circle">' . $row['n2'] . '</span></td><td>' . $row['days'] . '</td><td>' . htmlspecialchars($row['date']) . '</td><td>' . $row['max_fois'] . '</td><td>' . $row['max_days'] . '</td></tr>';
}

$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $val = $daysAgo ?? 0;
    $class = $val <= 9 ? 'color-range-1' : ($val <= 14 ? 'color-range-2' : ($val <= 20 ? 'color-range-3' : 'color-range-4'));
    $count = $freqStats[$num] ?? 0;
    $numberStatsHTML .= "<tr class='$class'><td><span class='circle'>$num</span></td><td>" . ($daysAgo ?? '-') . "</td><td><span class='x-small'>x</span>$count</td></tr>";
}

echo str_replace(
    ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
    [$tableHTML, $comboHTML, $numberStatsHTML, ''],
    $template
);
