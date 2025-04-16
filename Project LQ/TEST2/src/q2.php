<?php
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Режим переключателя
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$tableMain  = $isNorder ? 'Q2_stats_norder'      : 'Q2_stats_order';
$tableComb  = 'Q2_combo_stats_order';

// 1. Основная таблица
$sql = "SELECT * FROM $tableMain ORDER BY Tirage DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// 2. Комбинации
$comboRows = [];

if ($isNorder) {
    // Генерация комбинаций без учёта порядка
    $comboMap = [];
    $comboCount = [];
    foreach ($data as $row) {
        $nums = [$row['n1'], $row['n2']];
        sort($nums);
        $key = implode('-', $nums);
        if (!isset($comboCount[$key])) {
            $comboCount[$key] = 0;
        }
        $comboCount[$key]++;
        if (!isset($comboMap[$key]) || $comboMap[$key]['tirage'] < $row['Tirage']) {
            $comboMap[$key] = [
                'n1' => $nums[0],
                'n2' => $nums[1],
                'tirage' => $row['Tirage']
            ];
        }
    }

    foreach ($comboMap as $row) {
        $days = (new DateTime($row['tirage']))->diff(new DateTime())->days;
        $comboRows[] = [
            'n1' => $row['n1'],
            'n2' => $row['n2'],
            'days' => $days,
            'date' => $row['tirage'],
            'max_fois' => $comboCount["{$row['n1']}-{$row['n2']}"] ?? 0
        ];
    }

    usort($comboRows, fn($a, $b) => $b['days'] <=> $a['days']);
} else {
    // Комбинации из БД (порядок важен)
    $sqlCombo = "SELECT n1, n2, jours, tirage, max_fois FROM $tableComb ORDER BY jours DESC";
    $resCombo = $conn->query($sqlCombo);
    if ($resCombo && $resCombo->num_rows > 0) {
        while ($r = $resCombo->fetch_assoc()) {
            $comboRows[] = [
                'n1' => $r['n1'],
                'n2' => $r['n2'],
                'days' => $r['jours'],
                'date' => $r['tirage'],
                'max_fois' => $r['max_fois'] ?? '-'
            ];
        }
    }
}

// 3. Последний тираж для каждой цифры 0–9 (всегда из ORDER)
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

// Загрузка шаблона q2.html
ob_start();
include 'q2.html';
$template = ob_get_clean();

// --- Таблица 1 (основная)
$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach ($row as $key => $cell) {
        $tableHTML .= '<td>' . ($key === 'n1' || $key === 'n2' ? "<span class=\"circle\">$cell</span>" : htmlspecialchars($cell)) . '</td>';
    }
    $tableHTML .= '</tr>';
}

// --- Таблица 2 (комбинации)
$comboHTML = '';
foreach ($comboRows as $row) {
    $comboHTML .= "<tr>";
    $comboHTML .= "<td><span class='circle'>{$row['n1']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n2']}</span></td>";
    $comboHTML .= "<td>{$row['days']}</td>";
    $comboHTML .= "<td>{$row['date']}</td>";
    $comboHTML .= "<td>{$row['max_fois']}</td>";
    $comboHTML .= "</tr>";
}

// --- Таблица 3 (дни по цифрам, с подсветкой)
$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $circle = "<span class='circle'>$num</span>";
    $val = $daysAgo ?? 0;

    if ($val >= 1 && $val <= 10) {
        $class = 'color-range-1';
    } elseif ($val >= 11 && $val <= 15) {
        $class = 'color-range-2';
    } elseif ($val >= 16 && $val <= 20) {
        $class = 'color-range-3';
    } elseif ($val > 20) {
        $class = 'color-range-4';
    } else {
        $class = '';
    }

    $numberStatsHTML .= "<tr class=\"$class\"><td>$circle</td><td>" . ($daysAgo !== null ? $daysAgo : '-') . "</td></tr>";
}

// --- JS-переключатель
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

  toggle.addEventListener('change', () => {
    const next = toggle.checked ? '?norder=1' : '';
    window.location.href = 'index.php' + next;
  });
</script>";

// --- Вставка в шаблон
echo str_replace(
  ['<!--TABLE_PLACEHOLDER-->', '<!--COMBO_PLACEHOLDER-->', '<!--NUMBER_STATS_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
  [$tableHTML, $comboHTML, $numberStatsHTML, $script],
  $template
);