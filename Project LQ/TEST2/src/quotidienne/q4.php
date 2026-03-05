<?php
require_once "db.php";

/* ===============================
   Количество тиражей Q4
================================ */
$countResult = $conn->query("SELECT COUNT(*) as total FROM Q4");
$q4count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q4count = (int)$row['total'];
}

/* ===============================
   ORDER / N'importe
================================ */
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table     = $isNorder ? 'Q4_stats_norder' : 'Q4_stats_order';
$tableComb = $isNorder ? 'Q4_combo_stats_norder' : 'Q4_combo_stats_order';

/* ===============================
   Диапазон (как Q2 / Q3)
================================ */
$allowedRanges = [10, 20, 50, 100, 365];
$countRange = (isset($_GET['count_range']) && in_array((int)$_GET['count_range'], $allowedRanges, true))
    ? (int)$_GET['count_range']
    : 50;

/* ===============================
   Основная таблица (365)
================================ */
$sql = "SELECT * FROM $table ORDER BY Tirage DESC LIMIT 365";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

/* ===============================
   Комбинации (из БД: order или norder)
================================ */
$comboStats = [];
$sqlCombo = "
    SELECT n1, n2, n3, n4, days, Tirage, max_fois, max_days
    FROM $tableComb
    ORDER BY days DESC
";
$resCombo = $conn->query($sqlCombo);
if ($resCombo && $resCombo->num_rows > 0) {
    while ($r = $resCombo->fetch_assoc()) {
        $comboStats[] = [
            'n1'       => $r['n1'],
            'n2'       => $r['n2'],
            'n3'       => $r['n3'],
            'n4'       => $r['n4'],
            'days'     => $r['days'],
            'date'     => $r['Tirage'],
            'max_fois' => $r['max_fois'] ?? '-',
            'max_days' => $r['max_days'] ?? '-'
        ];
    }
}

/* ===============================
   Последний тираж цифр 0–9
================================ */
$daysStats = array_fill(0, 10, null);

$sqlLastNums = "
    SELECT n, MAX(Tirage) AS Last_Tirage
    FROM (
        SELECT n1 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n2 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n3 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n4 AS n, Tirage FROM Q4_stats_order
    ) AllNums
    GROUP BY n
";

$resLastNums = $conn->query($sqlLastNums);
if ($resLastNums && $resLastNums->num_rows > 0) {
    while ($r = $resLastNums->fetch_assoc()) {
        $daysStats[(int)$r['n']] =
            (new DateTime($r['Last_Tirage']))->diff(new DateTime())->days;
    }
}

/* ===============================
   Частота цифр за N тиражей
================================ */
$freqStats = array_fill(0, 10, 0);

$sqlFreq = "
    SELECT num AS digit, COUNT(*) AS cnt
    FROM (
        SELECT n1 AS num FROM (
            SELECT n1,n2,n3,n4 FROM Q4 ORDER BY Tirage DESC LIMIT $countRange
        ) t1
        UNION ALL
        SELECT n2 AS num FROM (
            SELECT n1,n2,n3,n4 FROM Q4 ORDER BY Tirage DESC LIMIT $countRange
        ) t2
        UNION ALL
        SELECT n3 AS num FROM (
            SELECT n1,n2,n3,n4 FROM Q4 ORDER BY Tirage DESC LIMIT $countRange
        ) t3
        UNION ALL
        SELECT n4 AS num FROM (
            SELECT n1,n2,n3,n4 FROM Q4 ORDER BY Tirage DESC LIMIT $countRange
        ) t4
    ) allnums
    GROUP BY num
";

$resFreq = $conn->query($sqlFreq);
if ($resFreq && $resFreq->num_rows > 0) {
    while ($r = $resFreq->fetch_assoc()) {
        $digit = (int)$r['digit'];
        if ($digit >= 0 && $digit <= 9) {
            $freqStats[$digit] = (int)$r['cnt'];
        }
    }
}

$conn->close();

/* ===============================
   Шаблон
================================ */
ob_start();
include 'q4.html';
$template = ob_get_clean();

/* ===============================
   Таблица 1
================================ */
$tableHTML = '';
foreach ($data as $row) {
    $nums = [$row['n1'], $row['n2'], $row['n3'], $row['n4']];
    $isAllUnique = count(array_unique($nums)) === 4;
    $fois = isset($row['fois']) ? (int)$row['fois'] : 0;

    $classes = [];
    if ($isAllUnique) $classes[] = 'highlight-row';

    $rowClass = $classes ? " class='" . implode(' ', $classes) . "'" : "";

    $tableHTML .= "<tr$rowClass>";
    foreach (['Tirage', 'n1', 'n2', 'n3', 'n4', 'days', 'days2', 'fois', 'max_days'] as $key) {
        $cell = htmlspecialchars((string)($row[$key] ?? ''));

        if (in_array($key, ['n1','n2','n3','n4'])) {
            $tableHTML .= "<td><span class='circle'>$cell</span></td>";
        } elseif ($key === 'fois') {
            if ($fois === 1 || $fois === 2) {
                $class = $fois === 1 ? 'star-box star-1' : 'star-box star-2';
                $tableHTML .= "<td><span class='$class'>$fois</span></td>";
            } else {
                $tableHTML .= "<td>$cell</td>";
            }
        } else {
            $tableHTML .= "<td>$cell</td>";
        }
    }
    $tableHTML .= '</tr>';
}

/* ===============================
   Таблица 2
================================ */
$comboHTML = '';
foreach ($comboStats as $row) {
    $comboHTML .= "<tr>
        <td><span class='circle'>{$row['n1']}</span></td>
        <td><span class='circle'>{$row['n2']}</span></td>
        <td><span class='circle'>{$row['n3']}</span></td>
        <td><span class='circle'>{$row['n4']}</span></td>
        <td>{$row['days']}</td>
        <td>{$row['date']}</td>
        <td>{$row['max_fois']}</td>
        <td>{$row['max_days']}</td>
    </tr>";
}

/* ===============================
   Таблица 3 (дни + xN)
================================ */
$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $val = $daysAgo ?? 0;
    $class = $val <= 10 ? 'color-range-1'
        : ($val <= 15 ? 'color-range-2'
        : ($val <= 20 ? 'color-range-3' : 'color-range-4'));

    $count = $freqStats[$num] ?? 0;
    $circle = "<span class='circle'>$num</span>";

    $numberStatsHTML .= "<tr class='$class'>
        <td>$circle</td>
        <td>" . ($daysAgo ?? '-') . "</td>
        <td><span class='x-small'>x</span>$count</td>
    </tr>";
}

/* ===============================
   JS (только toggle)
================================ */
$script = "<script>
const toggle = document.getElementById('toggleSwitch');
const labelOrder = document.getElementById('labelOrder');
const labelNimport = document.getElementById('labelNimport');
const neonSwitch = document.getElementById('neonSwitch');
toggle.checked = " . ($isNorder ? 'true' : 'false') . ";
labelOrder.classList.toggle('active', !toggle.checked);
labelNimport.classList.toggle('active', toggle.checked);
neonSwitch.classList.toggle('active', toggle.checked);
</script>";

echo str_replace(
  ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
  [$tableHTML, $comboHTML, $numberStatsHTML, $script],
  $template
);