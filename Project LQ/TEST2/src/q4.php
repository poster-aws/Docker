<?php
// === Q4.PHP ===
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table     = $isNorder ? 'Q4_stats_norder' : 'Q4_stats_order';
$tableComb = 'Q4_combo_stats_order';

$sql = "SELECT * FROM $table ORDER BY Tirage DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$comboStats = [];

if ($isNorder) {
    // Комбинации в PHP (norder)
    $comboMap = [];
    foreach ($data as $row) {
        $nums = [$row['n1'], $row['n2'], $row['n3'], $row['n4']];
        sort($nums);
        $key = implode('-', $nums);
        if (!isset($comboMap[$key]) || $comboMap[$key]['tirage'] < $row['Tirage']) {
            $comboMap[$key] = [
                'n1' => $nums[0],
                'n2' => $nums[1],
                'n3' => $nums[2],
                'n4' => $nums[3],
                'tirage' => $row['Tirage']
            ];
        }
    }
    foreach ($comboMap as $row) {
        $days = (new DateTime($row['tirage']))->diff(new DateTime())->days;
        $comboStats[] = [
            'n1' => $row['n1'], 'n2' => $row['n2'], 'n3' => $row['n3'], 'n4' => $row['n4'],
            'days' => $days, 'date' => $row['tirage']
        ];
    }
    usort($comboStats, fn($a, $b) => $b['days'] <=> $a['days']);
} else {
    // Комбинации из БД (order)
    $sqlCombo = "SELECT n1, n2, n3, n4, jours, tirage FROM $tableComb ORDER BY jours DESC";
    $resCombo = $conn->query($sqlCombo);
    if ($resCombo && $resCombo->num_rows > 0) {
        while ($r = $resCombo->fetch_assoc()) {
            $comboStats[] = [
                'n1' => $r['n1'], 'n2' => $r['n2'], 'n3' => $r['n3'], 'n4' => $r['n4'],
                'days' => $r['jours'], 'date' => $r['tirage']
            ];
        }
    }
}

$daysStats = array_fill(0, 10, null);
$sqlLastNums = "
    SELECT n, MAX(Tirage) AS Last_Tirage FROM (
        SELECT n1 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n2 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n3 AS n, Tirage FROM Q4_stats_order
        UNION ALL
        SELECT n4 AS n, Tirage FROM Q4_stats_order
    ) AS AllNums GROUP BY n
";
$resLastNums = $conn->query($sqlLastNums);
if ($resLastNums && $resLastNums->num_rows > 0) {
    while ($r = $resLastNums->fetch_assoc()) {
        $days = (new DateTime($r['Last_Tirage']))->diff(new DateTime())->days;
        $daysStats[(int)$r['n']] = $days;
    }
}
$conn->close();

ob_start();
include 'q4.html';
$template = ob_get_clean();

$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach (['Tirage', 'n1', 'n2', 'n3', 'n4', 'days', 'days2', 'fois', 'max'] as $key) {
        $cell = $row[$key];
        $tableHTML .= in_array($key, ['n1','n2','n3','n4']) ? "<td><span class='circle'>$cell</span></td>" : "<td>$cell</td>";
    }
    $tableHTML .= '</tr>';
}

$comboHTML = '';
foreach ($comboStats as $row) {
    $comboHTML .= "<tr>";
    foreach (['n1','n2','n3','n4'] as $k) {
        $comboHTML .= "<td><span class='circle'>{$row[$k]}</span></td>";
    }
    $comboHTML .= "<td>{$row['days']}</td><td>{$row['date']}</td></tr>";
}

$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $val = $daysAgo ?? 0;
    $class = $val <= 10 ? 'color-range-1' : ($val <= 15 ? 'color-range-2' : ($val <= 20 ? 'color-range-3' : 'color-range-4'));
    $circle = "<span class='circle'>$num</span>";
    $numberStatsHTML .= "<tr class='$class'><td>$circle</td><td>" . ($daysAgo ?? '-') . "</td></tr>";
}

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
