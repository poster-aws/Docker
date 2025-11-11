<?php
require_once './db.php';  // подключение $bancoConn

// Используем существующее соединение
$db = $bancoConn;

// === 1. Получаем параметр limit (50 или 200) ===
$allowedLimits = [50, 200];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits) ? (int)$_GET['limit'] : 50;

// === 2. Получаем общее количество тиражей в БД ===
$countRes = $db->query("SELECT COUNT(*) AS total FROM banco");
$totalCount = ($countRes && $row = $countRes->fetch_assoc()) ? (int)$row['total'] : 0;

// === 3. Получаем последние N тиражей ===
$query = "SELECT Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10,
                 n11, n12, n13, n14, n15, n16, n17, n18, n19, n20, turbo
          FROM banco
          ORDER BY Tirage DESC
          LIMIT $limit";
$result = $db->query($query);

// Если тиражей нет — выводим сообщение
if (!$result || $result->num_rows === 0) {
    echo "<p class='no-data'>Данных пока нет.</p>";
    exit;
}

$tirages = [];
while ($row = $result->fetch_assoc()) {
    $tirages[] = $row;
}

// === 4. Подсчёт Tot (частота каждого числа 1..70) ===
$totals = array_fill(1, 70, 0);
foreach ($tirages as $tirage) {
    for ($i = 1; $i <= 20; $i++) {
        $num = (int)$tirage["n$i"];
        if ($num >= 1 && $num <= 70) {
            $totals[$num]++;
        }
    }
}

// === 5. Динамические пороги для градации цвета Tot
$maxTot = max($totals);
$thresholdHigh = $maxTot * 0.66;
$thresholdMedium = $maxTot * 0.33;

// === 6. Мета-блок с данными для JS (общее кол-во и лимит)
echo "<div id='banco-meta' data-count='{$totalCount}' data-limit='{$limit}'></div>";

// === 7. Построение сетки
echo "<div class='table-wrapper banco-grid-wrapper' data-limit='{$limit}'><table class='digit-grid'>";

// 7.1 Шапка: строка с датами
echo "<thead><tr class='date-row'><th class='sticky-left tot-label'></th><th class='digit-label'></th>";
foreach ($tirages as $tirage) {
    $date = $tirage['Tirage'];
    echo "<th><div class='vertical-text'>$date</div></th>";
}
echo "</tr>";

// 7.2 Шапка: строка turbo (ниже дат)
echo "<tr class='turbo-row'><th class='sticky-left tot-label'></th><th class='digit-label'></th>";
foreach ($tirages as $tirage) {
    $turbo = htmlspecialchars($tirage['turbo'] . 'x', ENT_QUOTES);
    echo "<th>$turbo</th>";
}
echo "</tr></thead>";

// 7.3 Строки чисел
for ($digit = 1; $digit <= 70; $digit++) {
    $tot = $totals[$digit];
    $totClass = ($tot >= $thresholdHigh) ? 'high' : (($tot >= $thresholdMedium) ? 'medium' : 'low');

    echo "<tr>
        <td class='sticky-left tot-label $totClass'>{$tot}x</td>
        <td class='digit-label'>$digit</td>";
    
    foreach ($tirages as $tirage) {
        $nums = array_slice($tirage, 1, 20, true); // только n1..n20
        $isHit = in_array($digit, $nums);
        echo "<td" . ($isHit ? " class='hit'" : "") . ">" . ($isHit ? $digit : "") . "</td>";
    }
    
    echo "</tr>";
}

echo "</table></div>";