<?php
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table = $isNorder ? 'Q4_stats_norder' : 'Q4_stats_order';

// 1. Основная таблица
$sql = "SELECT * FROM $table ORDER BY Tirage DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// 2. Комбинации 4 чисел — группировка в PHP
$sqlCombo = "SELECT n1, n2, n3, n4, Tirage FROM $table";
$resCombo = $conn->query($sqlCombo);

$comboMap = [];
if ($resCombo && $resCombo->num_rows > 0) {
    while ($r = $resCombo->fetch_assoc()) {
        $nums = [$r['n1'], $r['n2'], $r['n3'], $r['n4']];
        sort($nums);
        $key = implode('-', $nums);
        if (!isset($comboMap[$key]) || $comboMap[$key]['tirage'] < $r['Tirage']) {
            $comboMap[$key] = [
                'n1' => $nums[0],
                'n2' => $nums[1],
                'n3' => $nums[2],
                'n4' => $nums[3],
                'tirage' => $r['Tirage']
            ];
        }
    }
}

$comboStats = [];
foreach ($comboMap as $row) {
    $days = (new DateTime($row['tirage']))->diff(new DateTime())->days;
    $comboStats[] = [
        'n1' => $row['n1'],
        'n2' => $row['n2'],
        'n3' => $row['n3'],
        'n4' => $row['n4'],
        'days' => $days,
        'date' => $row['tirage']
    ];
}
usort($comboStats, fn($a, $b) => $b['days'] <=> $a['days']);

// 3. Последний тираж для цифр 0–9
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
    ) AS AllNums
    GROUP BY n
";
$resLastNums = $conn->query($sqlLastNums);
if ($resLastNums && $resLastNums->num_rows > 0) {
    while ($r = $resLastNums->fetch_assoc()) {
        $days = (new DateTime($r['Last_Tirage']))->diff(new DateTime())->days;
        $daysStats[(int)$r['n']] = $days;
    }
}

$conn->close();

// Загрузка шаблона HTML
ob_start();
include 'q4.html';
$template = ob_get_clean();

// Генерация таблиц
$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach (['Tirage', 'n1', 'n2', 'n3', 'n4', 'days', 'days2', 'fois', 'max'] as $key) {
        $cell = $row[$key];
        if (in_array($key, ['n1', 'n2', 'n3', 'n4'])) {
            $tableHTML .= "<td><span class='circle'>$cell</span></td>";
        } else {
            $tableHTML .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
    }
    $tableHTML .= '</tr>';
}

$comboHTML = '';
foreach ($comboStats as $row) {
    $comboHTML .= "<tr>";
    $comboHTML .= "<td><span class='circle'>{$row['n1']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n2']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n3']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n4']}</span></td>";
    $comboHTML .= "<td>{$row['days']}</td>";
    $comboHTML .= "<td>{$row['date']}</td>";
    $comboHTML .= "</tr>";
}

$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $circle = "<span class='circle'>$num</span>";
    $numberStatsHTML .= "<tr><td>$circle</td><td>" . ($daysAgo !== null ? $daysAgo : '-') . "</td></tr>";
}

$script = "<script>
  const toggle = document.getElementById('toggleSwitch');
  const labelOrder = document.getElementById('labelOrder');
  const labelNimport = document.getElementById('labelNimport');
  const neonSwitch = document.getElementById('neonSwitch');

  const setActiveLabels = () => {
    const isChecked = toggle.checked;
    labelOrder.classList.toggle('active', !isChecked);
    labelNimport.classList.toggle('active', isChecked);
    neonSwitch.classList.toggle('active', isChecked);
  };

  toggle.checked = " . ($isNorder ? 'true' : 'false') . ";
  setActiveLabels();
</script>";

echo str_replace(
  ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
  [$tableHTML, $comboHTML, $numberStatsHTML, $script],
  $template
);