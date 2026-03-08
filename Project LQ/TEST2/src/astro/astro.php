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

/* Переключатель вида: mois (Mois+Signe) или jour (Jour+Année) */
$astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';

/* Диапазон для третьего столбца: 30, 100, 365 или tout (все тиражи) */
$allowedRanges = [30, 100, 365];
$countRangeParam = $_GET['count_range'] ?? '100';
$countRange = ($countRangeParam === 'all') ? 'all' : (in_array((int)$countRangeParam, $allowedRanges, true) ? (int)$countRangeParam : 100);
$limitClause = ($countRange === 'all') ? '' : ' LIMIT ' . (int)$countRange;

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
$sqlFreq = "
    SELECT jour, COUNT(*) AS cnt
    FROM Astro_stats
    WHERE Tirage IN (
        SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ") AS t
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

/* Статистика по mois (1–12): last_tirage и fois за последние N тиражей */
$moisStats = [];
$resMois = $astroConn->query("SELECT mois, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY mois");
if ($resMois && $resMois->num_rows > 0) {
    while ($row = $resMois->fetch_assoc()) {
        $m = (int)$row['mois'];
        if ($m >= 1 && $m <= 12) {
            $moisStats[$m] = ['last_tirage' => $row['last_tirage']];
        }
    }
}
$freqMois = array_fill(1, 12, 0);
$sqlFreqMois = "
    SELECT mois, COUNT(*) AS cnt
    FROM Astro_stats
    WHERE Tirage IN (
        SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ") AS t
    )
    GROUP BY mois
";
$resFreqMois = $astroConn->query($sqlFreqMois);
if ($resFreqMois && $resFreqMois->num_rows > 0) {
    while ($row = $resFreqMois->fetch_assoc()) {
        $m = (int)$row['mois'];
        if ($m >= 1 && $m <= 12) {
            $freqMois[$m] = (int)$row['cnt'];
        }
    }
}

/* Статистика по annee (0–99): last_tirage и fois за последние N тиражей */
$anneeStats = [];
$resAnnee = $astroConn->query("SELECT annee, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY annee");
if ($resAnnee && $resAnnee->num_rows > 0) {
    while ($row = $resAnnee->fetch_assoc()) {
        $a = (int)$row['annee'];
        if ($a >= 0 && $a <= 99) {
            $anneeStats[$a] = ['last_tirage' => $row['last_tirage']];
        }
    }
}
$freqAnnee = array_fill(0, 100, 0);
$sqlFreqAnnee = "
    SELECT annee, COUNT(*) AS cnt
    FROM Astro_stats
    WHERE Tirage IN (
        SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ") AS t
    )
    GROUP BY annee
";
$resFreqAnnee = $astroConn->query($sqlFreqAnnee);
if ($resFreqAnnee && $resFreqAnnee->num_rows > 0) {
    while ($row = $resFreqAnnee->fetch_assoc()) {
        $a = (int)$row['annee'];
        if ($a >= 0 && $a <= 99) {
            $freqAnnee[$a] = (int)$row['cnt'];
        }
    }
}

/* Статистика по signe (1–12): last_tirage и fois за последние N тиражей */
$signeStats = [];
$resSigne = $astroConn->query("SELECT signe, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY signe");
if ($resSigne && $resSigne->num_rows > 0) {
    while ($row = $resSigne->fetch_assoc()) {
        $s = (int)$row['signe'];
        if ($s >= 1 && $s <= 12) {
            $signeStats[$s] = ['last_tirage' => $row['last_tirage']];
        }
    }
}
$freqSigne = array_fill(1, 12, 0);
$sqlFreqSigne = "
    SELECT signe, COUNT(*) AS cnt
    FROM Astro_stats
    WHERE Tirage IN (
        SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ") AS t
    )
    GROUP BY signe
";
$resFreqSigne = $astroConn->query($sqlFreqSigne);
if ($resFreqSigne && $resFreqSigne->num_rows > 0) {
    while ($row = $resFreqSigne->fetch_assoc()) {
        $s = (int)$row['signe'];
        if ($s >= 1 && $s <= 12) {
            $freqSigne[$s] = (int)$row['cnt'];
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

$moisStatsHTML = '';
for ($m = 1; $m <= 12; $m++) {
    $joursPassesM = '';
    if (isset($moisStats[$m])) {
        $last = $moisStats[$m]['last_tirage'];
        $joursPassesM = (string)$today->diff(new DateTime($last))->days;
    }
    $valM = $joursPassesM !== '' ? (int)$joursPassesM : 0;
    $classM = $valM <= 9 ? 'color-range-1' : ($valM <= 14 ? 'color-range-2' : ($valM <= 20 ? 'color-range-3' : 'color-range-4'));
    $countM = $freqMois[$m] ?? 0;
    $moisStatsHTML .= '<tr class="' . $classM . '">';
    $moisStatsHTML .= '<td>' . htmlspecialchars($mois_fr[$m]) . '</td>';
    $moisStatsHTML .= '<td>' . htmlspecialchars($joursPassesM) . '</td>';
    $moisStatsHTML .= '<td><span class="x-small">x</span>' . $countM . '</td>';
    $moisStatsHTML .= '</tr>';
}

$anneeStatsHTML = '';
for ($a = 0; $a <= 99; $a++) {
    $joursPassesA = '';
    if (isset($anneeStats[$a])) {
        $last = $anneeStats[$a]['last_tirage'];
        $joursPassesA = (string)$today->diff(new DateTime($last))->days;
    }
    $valA = $joursPassesA !== '' ? (int)$joursPassesA : 0;
    $classA = $valA <= 9 ? 'color-range-1' : ($valA <= 14 ? 'color-range-2' : ($valA <= 20 ? 'color-range-3' : 'color-range-4'));
    $countA = $freqAnnee[$a] ?? 0;
    $anneeStatsHTML .= '<tr class="' . $classA . '">';
    $anneeStatsHTML .= '<td>' . sprintf('%02d', $a) . '</td>';
    $anneeStatsHTML .= '<td>' . htmlspecialchars($joursPassesA) . '</td>';
    $anneeStatsHTML .= '<td><span class="x-small">x</span>' . $countA . '</td>';
    $anneeStatsHTML .= '</tr>';
}

$signeStatsHTML = '';
for ($s = 1; $s <= 12; $s++) {
    $joursPassesS = '';
    if (isset($signeStats[$s])) {
        $last = $signeStats[$s]['last_tirage'];
        $joursPassesS = (string)$today->diff(new DateTime($last))->days;
    }
    $valS = $joursPassesS !== '' ? (int)$joursPassesS : 0;
    $classS = $valS <= 9 ? 'color-range-1' : ($valS <= 14 ? 'color-range-2' : ($valS <= 20 ? 'color-range-3' : 'color-range-4'));
    $countS = $freqSigne[$s] ?? 0;
    $signeDisplay = $signe_symb[$s] . ' ' . $signe_abr[$s];
    $signeStatsHTML .= '<tr class="' . $classS . '">';
    $signeStatsHTML .= '<td>' . htmlspecialchars($signeDisplay) . '</td>';
    $signeStatsHTML .= '<td>' . htmlspecialchars($joursPassesS) . '</td>';
    $signeStatsHTML .= '<td><span class="x-small">x</span>' . $countS . '</td>';
    $signeStatsHTML .= '</tr>';
}

$template = str_replace('<!--TABLE_PLACEHOLDER-->', $tableHTML, $template);
$template = str_replace('<!--JOUR_STATS_PLACEHOLDER-->', $jourStatsHTML, $template);
$template = str_replace('<!--MOIS_STATS_PLACEHOLDER-->', $moisStatsHTML, $template);
$template = str_replace('<!--ANNEE_STATS_PLACEHOLDER-->', $anneeStatsHTML, $template);
$template = str_replace('<!--SIGNE_STATS_PLACEHOLDER-->', $signeStatsHTML, $template);
echo $template;
