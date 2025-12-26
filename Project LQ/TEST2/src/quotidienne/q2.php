<?php
require_once "db.php";

// Подсчёт количества записей в Q2 для заголовка
$countQuery = "SELECT COUNT(*) as total FROM Q2";
$countResult = $conn->query($countQuery);
$q2count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q2count = (int)$row['total'];
}

// Режим переключателя (ORDER / N'import)
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$tableMain  = $isNorder ? 'Q2_stats_norder' : 'Q2_stats_order';
$tableComb  = 'Q2_combo_stats_order';

// Диапазон для подсчёта количества появлений цифры за N последних тиражей
$allowedRanges = [10, 20, 50, 100, 365];
$countRange = (isset($_GET['count_range']) && in_array((int)$_GET['count_range'], $allowedRanges, true))
    ? (int)$_GET['count_range']
    : 50; // значение по умолчанию

// 1. Основная таблица (ТОЛЬКО 365 последних)
$sql = "SELECT * FROM $tableMain ORDER BY Tirage DESC LIMIT 365";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// 2. Таблица комбинаций (добавляем max_jours_passés за всю историю)
$comboRows = [];

if ($isNorder) {

    // N'IMPORT: нормализуем пару (LEAST/GREATEST) и считаем:
    // - jours (с последнего появления)
    // - tirage (последняя дата)
    // - max_fois (сколько раз выпадала)
    // - max_jours (макс. разрыв между появлениями + разрыв до сегодня) по всей истории
    $sqlCombo = "
        WITH base AS (
            SELECT
                LEAST(n1, n2)    AS a,
                GREATEST(n1, n2) AS b,
                Tirage
            FROM Q2
        ),
        gaps AS (
            SELECT
                a, b, Tirage,
                DATEDIFF(Tirage, LAG(Tirage) OVER (PARTITION BY a, b ORDER BY Tirage)) AS gap_days
            FROM base
        ),
        agg AS (
            SELECT
                a, b,
                MAX(Tirage) AS last_tirage,
                COUNT(*)    AS max_fois,
                GREATEST(
                    IFNULL(MAX(gap_days), 0),
                    DATEDIFF(CURDATE(), MAX(Tirage))
                ) AS max_jours
            FROM gaps
            GROUP BY a, b
        )
        SELECT
            a AS n1,
            b AS n2,
            DATEDIFF(CURDATE(), last_tirage) AS jours,
            last_tirage AS tirage,
            max_jours,
            max_fois
        FROM agg
        ORDER BY jours DESC
    ";

    $resCombo = $conn->query($sqlCombo);
    if ($resCombo && $resCombo->num_rows > 0) {
        while ($r = $resCombo->fetch_assoc()) {
            $comboRows[] = [
                'n1'       => $r['n1'],
                'n2'       => $r['n2'],
                'days'     => $r['jours'],
                'date'     => $r['tirage'],
                'max_days' => $r['max_jours'],
                'max_fois' => $r['max_fois']
            ];
        }
    }

} else {

    // ORDER: берём jours/tirage/max_fois из таблицы combos,
    // и добавляем max_jours (за всю историю) из Q2 через оконную функцию
    $sqlCombo = "
        WITH gaps AS (
            SELECT
                n1, n2, Tirage,
                DATEDIFF(Tirage, LAG(Tirage) OVER (PARTITION BY n1, n2 ORDER BY Tirage)) AS gap_days
            FROM Q2
        ),
        agg AS (
            SELECT
                n1, n2,
                GREATEST(
                    IFNULL(MAX(gap_days), 0),
                    DATEDIFF(CURDATE(), MAX(Tirage))
                ) AS max_jours
            FROM gaps
            GROUP BY n1, n2
        )
        SELECT
            cs.n1, cs.n2,
            cs.jours, cs.tirage,
            a.max_jours,
            cs.max_fois
        FROM $tableComb cs
        LEFT JOIN agg a
               ON a.n1 = cs.n1 AND a.n2 = cs.n2
        ORDER BY cs.jours DESC
    ";

    $resCombo = $conn->query($sqlCombo);
    if ($resCombo && $resCombo->num_rows > 0) {
        while ($r = $resCombo->fetch_assoc()) {
            $comboRows[] = [
                'n1'       => $r['n1'],
                'n2'       => $r['n2'],
                'days'     => $r['jours'],
                'date'     => $r['tirage'],
                'max_days' => $r['max_jours'] ?? 0,
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
        $idx = (int)$r['n'];
        if ($idx >= 0 && $idx <= 9) {
            $daysStats[$idx] = $days;
        }
    }
}

// 4. Частота появления цифр за N последних тиражей (всегда из оригинальной Q2)
$freqStats = array_fill(0, 10, 0);

$sqlFreq = "
    SELECT num AS digit, COUNT(*) AS cnt
    FROM (
        SELECT n1 AS num
        FROM (
            SELECT n1, n2
            FROM Q2
            ORDER BY Tirage DESC
            LIMIT $countRange
        ) AS t1
        UNION ALL
        SELECT n2 AS num
        FROM (
            SELECT n1, n2
            FROM Q2
            ORDER BY Tirage DESC
            LIMIT $countRange
        ) AS t2
    ) AS allnums
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

// Загрузка шаблона q2.html
ob_start();
include 'q2.html';
$template = ob_get_clean();

// --- Таблица 1 (основная) — без колонки Max jours passés
$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    foreach ($row as $key => $cell) {

        // ✅ колонку Max (какое бы имя ни было в таблице) не выводим в 1-й таблице
        if (
            $key === 'max' ||
            $key === 'max_jours' ||
            $key === 'max_jours_passes' ||
            $key === 'max_jours_passés' ||
            $key === 'maxjours' ||
            $key === 'max_jours_passes_total'
        ) {
            continue;
        }

        if ($key === 'n1' || $key === 'n2') {
            $tableHTML .= '<td><span class="circle">' . htmlspecialchars($cell) . '</span></td>';
        } else {
            $tableHTML .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
    }
    $tableHTML .= '</tr>';
}

// --- Таблица 2 (комбинации) — с колонкой Max jours passés
$comboHTML = '';
foreach ($comboRows as $row) {
    $comboHTML .= '<tr>';
    $comboHTML .= "<td><span class='circle'>{$row['n1']}</span></td>";
    $comboHTML .= "<td><span class='circle'>{$row['n2']}</span></td>";
    $comboHTML .= "<td>{$row['days']}</td>";
    $comboHTML .= "<td>{$row['date']}</td>";
    $comboHTML .= "<td>" . ($row['max_days'] ?? 0) . "</td>";
    $comboHTML .= "<td>{$row['max_fois']}</td>";
    $comboHTML .= '</tr>';
}

// --- Таблица 3 (дни по цифрам + количество за N тиражей, с подсветкой)
$numberStatsHTML = '';
foreach ($daysStats as $num => $daysAgo) {
    $val = $daysAgo ?? 0;
    $class = $val <= 9
        ? 'color-range-1'
        : ($val <= 14
            ? 'color-range-2'
            : ($val <= 20
                ? 'color-range-3'
                : 'color-range-4'));

    $circle = "<span class='circle'>{$num}</span>";
    $count  = $freqStats[$num] ?? 0;

    $numberStatsHTML .= "<tr class='{$class}'>"
        . "<td>{$circle}</td>"
        . "<td>" . ($daysAgo ?? '-') . "</td>"
        . "<td><span class='x-small'>x</span>{$count}</td>"
        . "</tr>";
}

// ❗ Локальный JS нам больше не нужен — всё делает глобальный script.js
$script = "";

// --- Вставка в шаблон
echo str_replace(
    [
        '<!--TABLE_PLACEHOLDER-->',
        '<!--COMBO_PLACEHOLDER-->',
        '<!--NUMBER_STATS_PLACEHOLDER-->',
        '<!--SCRIPT_PLACEHOLDER-->'
    ],
    [
        $tableHTML,
        $comboHTML,
        $numberStatsHTML,
        $script
    ],
    $template
);