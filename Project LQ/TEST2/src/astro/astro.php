<?php
require_once "db.php";

/* Справочники: mois en français; signe = symbole Unicode (U+2648–U+2653) + abréviation */
$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
$signe_symb = [
    1 => "\u{2648}", 2 => "\u{2649}", 3 => "\u{264A}", 4 => "\u{264B}", 5 => "\u{264C}", 6 => "\u{264D}",
    7 => "\u{264E}", 8 => "\u{264F}", 9 => "\u{2650}", 10 => "\u{2651}", 11 => "\u{2652}", 12 => "\u{2653}"
];
$signe_abr = [
    1 => 'BEL', 2 => 'TAU', 3 => 'GEM', 4 => 'CAN', 5 => 'LEO', 6 => 'VIE',
    7 => 'BAL', 8 => 'SCO', 9 => 'SAG', 10 => 'CAP', 11 => 'VER', 12 => 'POI'
];

/* Количество записей для заголовка */
$countResult = $astroConn->query("SELECT COUNT(*) AS total FROM Astro_stats");
$astroCount = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $astroCount = (int)$row['total'];
}

/* Таблица: 365 последних записей из Astro_stats (mois/signe numériques en BDD) */
$sql = "SELECT Tirage, jour, mois, annee, signe, fois, days FROM Astro_stats ORDER BY Tirage DESC LIMIT 365";
$result = $astroConn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

/* Диапазон для третьего столбца: кол-во последних тиражей (10…365) */
$allowedRanges = [10, 20, 50, 100, 365];
$countRange = (isset($_GET['count_range']) && in_array((int)$_GET['count_range'], $allowedRanges, true))
    ? (int)$_GET['count_range']
    : 50;

/* Статистика по jour (1–31): Jours passés от текущей даты; Fois — за последние $countRange тиражей */
$jourStats = [];
$resJour = $astroConn->query("SELECT jour, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY jour");
if ($resJour && $resJour->num_rows > 0) {
    while ($row = $resJour->fetch_assoc()) {
        $j = (int)$row['jour'];
        if ($j >= 1 && $j <= 31) {
            $jourStats[$j] = ['last_tirage' => $row['last_tirage']];
        }
    }
}
$today = new DateTime('today');

/* Fois по каждому jour за последние N тиражей (distinct Tirage) */
$freqStats = array_fill(1, 31, 0);
$countRange = (int)$countRange;
$sqlFreq = "
    SELECT jour, COUNT(*) AS cnt
    FROM Astro_stats
    WHERE Tirage IN (
        SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC LIMIT " . $countRange . ") AS t
    )
    GROUP BY jour
";
$resFreq = $astroConn->query($sqlFreq);
if ($resFreq && $resFreq->num_rows > 0) {
    while ($row = $resFreq->fetch_assoc()) {
        $j = (int)$row['jour'];
        if ($j >= 1 && $j <= 31) {
            $freqStats[$j] = (int)$row['cnt'];
        }
    }
}

$astroConn->close();

ob_start();
include 'astro.html';
$template = ob_get_clean();

$tableHTML = '';
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['Tirage'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['jour'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars($mois_fr[(int)($row['mois'] ?? 0)] ?? (string)($row['mois'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['annee'] ?? '')) . '</td>';
    $s = (int)($row['signe'] ?? 0);
    $signe_display = isset($signe_symb[$s], $signe_abr[$s]) ? $signe_symb[$s] . ' ' . $signe_abr[$s] : (string)($row['signe'] ?? '');
    $tableHTML .= '<td>' . htmlspecialchars($signe_display) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['fois'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['days'] ?? '')) . '</td>';
    $tableHTML .= '</tr>';
}

$jourStatsHTML = '';
for ($j = 1; $j <= 31; $j++) {
    $joursPasses = '';
    if (isset($jourStats[$j])) {
        $last = $jourStats[$j]['last_tirage'];
        $joursPasses = (string)$today->diff(new DateTime($last))->days;
    }
    $val = $joursPasses !== '' ? (int)$joursPasses : 0;
    $class = $val <= 9 ? 'color-range-1' : ($val <= 14 ? 'color-range-2' : ($val <= 20 ? 'color-range-3' : 'color-range-4'));
    $count = $freqStats[$j] ?? 0;
    $jourStatsHTML .= '<tr class="' . $class . '">';
    $jourStatsHTML .= '<td>' . $j . '</td>';
    $jourStatsHTML .= '<td>' . htmlspecialchars($joursPasses) . '</td>';
    $jourStatsHTML .= '<td><span class="x-small">x</span>' . $count . '</td>';
    $jourStatsHTML .= '</tr>';
}

$template = str_replace('<!--TABLE_PLACEHOLDER-->', $tableHTML, $template);
$template = str_replace('<!--JOUR_STATS_PLACEHOLDER-->', $jourStatsHTML, $template);
echo $template;
