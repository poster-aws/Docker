<!-- src/banco/banco.css -->

<?php
require_once './db.php';  // db.php уже содержит готовое подключение $bancoConn

// Используем существующее соединение
$db = $bancoConn;

// ==== 1. Фиксированный лимит: 50 последних тиражей
$limit = 50;

// ==== 2. Получаем последние 50 тиражей
$query = "SELECT Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10,
                 n11, n12, n13, n14, n15, n16, n17, n18, n19, n20
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

// ==== 3. Подсчёт Tot (частота каждого числа 1..70)
$totals = array_fill(1, 70, 0);
foreach ($tirages as $tirage) {
    for ($i = 1; $i <= 20; $i++) {
        $num = (int)$tirage["n$i"];
        if ($num >= 1 && $num <= 70) {
            $totals[$num]++;
        }
    }
}

// ==== 3.1 Динамические пороги для градации цвета Tot (как в tout)
$maxTot = max($totals);
$thresholdHigh = $maxTot * 0.66;
$thresholdMedium = $maxTot * 0.33;

// ==== 4. Служебный мета-блок
echo "<div id='banco-meta' data-count='" . count($tirages) . "'></div>";

// ==== 5. Построение сетки
echo "<div class='table-wrapper banco-grid-wrapper'><table class='digit-grid'>";

// ==== 5.1 Заголовки столбцов (даты)
echo "<thead><tr><th class='sticky-left digit-label'></th><th class='sticky-left tot-label'></th>";
foreach ($tirages as $tirage) {
    $date = $tirage['Tirage']; // Предполагаем формат YYYY-MM-DD
    echo "<th><div class='vertical-text'>$date</div></th>";
}
echo "</tr></thead>";

// ==== 5.2 Строки (1-70)
for ($digit = 1; $digit <= 70; $digit++) {
    $tot = $totals[$digit];
    $totClass = ($tot >= $thresholdHigh) ? 'high' : (($tot >= $thresholdMedium) ? 'medium' : 'low');

    echo "<tr>
        <td class='sticky-left tot-label $totClass'>$tot</td>
        <td class='sticky-left digit-label'>$digit</td>";
    
    foreach ($tirages as $tirage) {
        $isHit = in_array($digit, array_slice($tirage, 1));
        echo "<td" . ($isHit ? " class='hit'" : "") . ">" . ($isHit ? $digit : "") . "</td>";
    }
    
    echo "</tr>";
}

echo "</table></div>";