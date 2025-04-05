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
$table = $isNorder ? 'Q2_stats_norder' : 'Q2_stats_order';

// Основная таблица
$sql = "SELECT * FROM $table ORDER BY Tirage DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Комбинации
$sqlCombo = $isNorder
    ? "SELECT LEAST(n1,n2) as a, GREATEST(n1,n2) as b, MAX(Tirage) as last FROM Q2_stats_norder GROUP BY a, b"
    : "SELECT n1, n2, MAX(Tirage) as last FROM Q2_stats_order GROUP BY n1, n2";
$resCombo = $conn->query($sqlCombo);
$comboRows = [];
if ($resCombo && $resCombo->num_rows > 0) {
    while ($r = $resCombo->fetch_assoc()) {
        $days = (new DateTime($r['last']))->diff(new DateTime())->days;
        $comboRows[] = [
            'n1' => $r['n1'] ?? $r['a'],
            'n2' => $r['n2'] ?? $r['b'],
            'days' => $days,
            'date' => $r['last']
        ];
    }
}
usort($comboRows, fn($a, $b) => $b['days'] <=> $a['days']);

// Всегда отображаемая таблица: дни с последнего появления числа (0–9)
$daysStats = array_fill(0, 10, null);
$sqlLastNums = "
    SELECT n, MAX(Tirage) AS Last_Tirage
    FROM (
        SELECT n1 AS n, Tirage FROM Q2_stats_order
        UNION ALL
        SELECT n2 AS n, Tirage FROM Q2_stats_order
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

// Загрузка HTML
ob_start();
include 'test.html';
$template = ob_get_clean();

// Таблица 1: последние тиражи
$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach ($row as $key => $cell) {
        $tableHTML .= '<td>' . ($key === 'n1' || $key === 'n2' ? "<span class=\"circle\">$cell</span>" : htmlspecialchars($cell)) . '</td>';
    }
    $tableHTML .= '</tr>';
}

// Таблица 2: комбинации
$comboHTML = '';
foreach ($comboRows as $row) {
    $comboHTML .= '<tr>';
    $comboHTML .= "<td><span class='circle'>{$row['n1']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n2']}</span></td>";
    $comboHTML .= "<td>{$row['days']}</td>";
    $comboHTML .= "<td>{$row['date']}</td>";
    $comboHTML .= '</tr>';
}

// Таблица 3: дни с последнего появления числа
$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $numberStatsHTML .= "<tr><td>$num</td><td>" . ($daysAgo !== null ? $daysAgo : '-') . "</td></tr>";
}

// Скрипт переключения
$script = "<script>
  const toggle = document.getElementById('toggleSwitch');
  const text = document.getElementById('switchText');
  toggle.checked = " . ($isNorder ? 'true' : 'false') . ";
  text.textContent = toggle.checked ? 'Dans n\\'importe quel ordre' : 'Dans l\\'ordre';
  toggle.addEventListener('change', () => {
    const next = toggle.checked ? '?norder=1' : '';
    window.location.href = 'index.php' + next;
  });
</script>";

// Вставка в шаблон
echo str_replace(
  ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
  [$tableHTML, $comboHTML, $numberStatsHTML, $script],
  $template
);