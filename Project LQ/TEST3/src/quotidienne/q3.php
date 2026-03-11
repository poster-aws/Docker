<?php
require_once "db.php";

$countResult = $conn->query("SELECT COUNT(*) as total FROM Q3");
$q3count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q3count = (int)$row['total'];
}

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table     = $isNorder ? 'Q3_stats_norder' : 'Q3_stats_order';
$tableComb = $isNorder ? 'Q3_combo_stats_norder' : 'Q3_combo_stats_order';

$allowedRanges = [10, 20, 50, 100, 365];
$countRange = (isset($_GET['count_range']) && in_array((int)$_GET['count_range'], $allowedRanges, true)) ? (int)$_GET['count_range'] : 50;

$sql = "SELECT * FROM $table ORDER BY Tirage DESC LIMIT 365";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $data[] = $row;
}

$comboStats = [];
$sqlCombo = "SELECT n1, n2, n3, days, Tirage, max_fois, max_days FROM $tableComb ORDER BY days DESC";
$resCombo = $conn->query($sqlCombo);
if ($resCombo && $resCombo->num_rows > 0) {
    while ($r = $resCombo->fetch_assoc()) {
        $comboStats[] = [
            'n1' => $r['n1'], 'n2' => $r['n2'], 'n3' => $r['n3'],
            'days' => $r['days'], 'date' => $r['Tirage'], 'max_fois' => $r['max_fois'] ?? '-', 'max_days' => $r['max_days'] ?? '-'
        ];
    }
}

$daysStats = array_fill(0, 10, null);
$sqlLastNums = "SELECT n, MAX(Tirage) AS Last_Tirage FROM (SELECT n1 AS n, Tirage FROM Q3_stats_order UNION ALL SELECT n2 AS n, Tirage FROM Q3_stats_order UNION ALL SELECT n3 AS n, Tirage FROM Q3_stats_order) AllNums GROUP BY n";
$resLastNums = $conn->query($sqlLastNums);
if ($resLastNums && $resLastNums->num_rows > 0) {
    while ($r = $resLastNums->fetch_assoc()) {
        $daysStats[(int)$r['n']] = (new DateTime($r['Last_Tirage']))->diff(new DateTime())->days;
    }
}

$freqStats = array_fill(0, 10, 0);
$sqlFreq = "SELECT num AS digit, COUNT(*) AS cnt FROM (SELECT n1 AS num FROM (SELECT n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT $countRange) t1 UNION ALL SELECT n2 AS num FROM (SELECT n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT $countRange) t2 UNION ALL SELECT n3 AS num FROM (SELECT n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT $countRange) t3) allnums GROUP BY num";
$resFreq = $conn->query($sqlFreq);
if ($resFreq && $resFreq->num_rows > 0) {
    while ($r = $resFreq->fetch_assoc()) {
        $d = (int)$r['digit'];
        if ($d >= 0 && $d <= 9) $freqStats[$d] = (int)$r['cnt'];
    }
}

$conn->close();

ob_start();
include 'q3.html';
$template = ob_get_clean();

$tableHTML = '';
foreach ($data as $row) {
    $n1 = $row['n1']; $n2 = $row['n2']; $n3 = $row['n3'];
    $highlightClass = ($n1 !== $n2 && $n1 !== $n3 && $n2 !== $n3) ? 'highlight-row' : '';
    $tableHTML .= "<tr class=\"$highlightClass\">";
    foreach (['Tirage','n1','n2','n3','days','days2','fois','max_days'] as $key) {
        $cell = htmlspecialchars((string)($row[$key] ?? ''));
        $tableHTML .= in_array($key, ['n1','n2','n3']) ? "<td><span class='circle'>$cell</span></td>" : "<td>$cell</td>";
    }
    $tableHTML .= '</tr>';
}

$comboHTML = '';
foreach ($comboStats as $row) {
    $comboHTML .= "<tr><td><span class='circle'>{$row['n1']}</span></td><td><span class='circle'>{$row['n2']}</span></td><td><span class='circle'>{$row['n3']}</span></td><td>{$row['days']}</td><td>" . htmlspecialchars($row['date']) . "</td><td>{$row['max_fois']}</td><td>{$row['max_days']}</td></tr>";
}

$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $val = $daysAgo ?? 0;
    $class = $val <= 9 ? 'color-range-1' : ($val <= 14 ? 'color-range-2' : ($val <= 20 ? 'color-range-3' : 'color-range-4'));
    $count = $freqStats[$num] ?? 0;
    $numberStatsHTML .= "<tr class='$class'><td><span class='circle'>$num</span></td><td>" . ($daysAgo ?? '-') . "</td><td><span class='x-small'>x</span>$count</td></tr>";
}

$script = '';
echo str_replace(
    ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
    [$tableHTML, $comboHTML, $numberStatsHTML, $script],
    $template
);
