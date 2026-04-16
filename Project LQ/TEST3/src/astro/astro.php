<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$signe_symb = [
    1 => "\u{2648}", 2 => "\u{2649}", 3 => "\u{264A}", 4 => "\u{264B}", 5 => "\u{264C}", 6 => "\u{264D}",
    7 => "\u{264E}", 8 => "\u{264F}", 9 => "\u{2650}", 10 => "\u{2651}", 11 => "\u{2652}", 12 => "\u{2653}",
];
$signe_abr = [
    1 => 'BEL', 2 => 'TAU', 3 => 'GEM', 4 => 'CAN', 5 => 'LEO', 6 => 'VIE',
    7 => 'BAL', 8 => 'SCO', 9 => 'SAG', 10 => 'CAP', 11 => 'VER', 12 => 'POI',
];

$hasStats = false;
$chk = $astroConn->query("SHOW TABLES LIKE 'Astro_stats'");
if ($chk && $chk->num_rows > 0) {
    $hasStats = true;
}

$astroCount = null;
$data = [];
$jourStats = [];
$freqStats = array_fill(1, 31, 0);
$moisStats = [];
$freqMois = array_fill(1, 12, 0);
$anneeStats = [];
$freqAnnee = array_fill(0, 100, 0);
$signeStats = [];
$freqSigne = array_fill(1, 12, 0);

// Сначала берём готовый счётчик из Astro_info (дешевле, чем COUNT(*) каждый запрос).
$infoTable = $astroConn->query("SHOW TABLES LIKE 'Astro_info'");
if ($infoTable && $infoTable->num_rows > 0) {
    $infoRes = $astroConn->query('SELECT Tirages FROM Astro_info LIMIT 1');
    if ($infoRes && $infoRow = $infoRes->fetch_assoc()) {
        $astroCount = (int) $infoRow['Tirages'];
    }
}

if ($hasStats) {
    if ($astroCount === null) {
        $countResult = $astroConn->query('SELECT COUNT(*) AS total FROM Astro_stats');
        if ($countResult && $row = $countResult->fetch_assoc()) {
            $astroCount = (int) $row['total'];
        }
    }

    $sql = 'SELECT Tirage, jour, mois, annee, signe, fois, days FROM Astro_stats ORDER BY Tirage DESC LIMIT 365';
    $result = $astroConn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';

    $allowedRanges = [30, 100, 365];
    $countRangeParam = $_GET['count_range'] ?? '100';
    $countRange = ($countRangeParam === 'all') ? 'all' : (in_array((int) $countRangeParam, $allowedRanges, true) ? (int) $countRangeParam : 100);
    $limitClause = ($countRange === 'all') ? '' : ' LIMIT ' . (int) $countRange;

    $resJour = $astroConn->query('SELECT jour, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY jour');
    if ($resJour && $resJour->num_rows > 0) {
        while ($row = $resJour->fetch_assoc()) {
            $j = (int) $row['jour'];
            if ($j >= 1 && $j <= 31) {
                $jourStats[$j] = ['last_tirage' => $row['last_tirage']];
            }
        }
    }
    $today = new DateTime('today');

    $sqlFreq = "
        SELECT jour, COUNT(*) AS cnt
        FROM Astro_stats
        WHERE Tirage IN (
            SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ') AS t
        )
        GROUP BY jour
    ';
    $resFreq = $astroConn->query($sqlFreq);
    if ($resFreq && $resFreq->num_rows > 0) {
        while ($row = $resFreq->fetch_assoc()) {
            $j = (int) $row['jour'];
            if ($j >= 1 && $j <= 31) {
                $freqStats[$j] = (int) $row['cnt'];
            }
        }
    }

    $resMois = $astroConn->query('SELECT mois, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY mois');
    if ($resMois && $resMois->num_rows > 0) {
        while ($row = $resMois->fetch_assoc()) {
            $m = (int) $row['mois'];
            if ($m >= 1 && $m <= 12) {
                $moisStats[$m] = ['last_tirage' => $row['last_tirage']];
            }
        }
    }
    $sqlFreqMois = "
        SELECT mois, COUNT(*) AS cnt
        FROM Astro_stats
        WHERE Tirage IN (
            SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ') AS t
        )
        GROUP BY mois
    ';
    $resFreqMois = $astroConn->query($sqlFreqMois);
    if ($resFreqMois && $resFreqMois->num_rows > 0) {
        while ($row = $resFreqMois->fetch_assoc()) {
            $m = (int) $row['mois'];
            if ($m >= 1 && $m <= 12) {
                $freqMois[$m] = (int) $row['cnt'];
            }
        }
    }

    $resAnnee = $astroConn->query('SELECT annee, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY annee');
    if ($resAnnee && $resAnnee->num_rows > 0) {
        while ($row = $resAnnee->fetch_assoc()) {
            $a = (int) $row['annee'];
            if ($a >= 0 && $a <= 99) {
                $anneeStats[$a] = ['last_tirage' => $row['last_tirage']];
            }
        }
    }
    $sqlFreqAnnee = "
        SELECT annee, COUNT(*) AS cnt
        FROM Astro_stats
        WHERE Tirage IN (
            SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ') AS t
        )
        GROUP BY annee
    ';
    $resFreqAnnee = $astroConn->query($sqlFreqAnnee);
    if ($resFreqAnnee && $resFreqAnnee->num_rows > 0) {
        while ($row = $resFreqAnnee->fetch_assoc()) {
            $a = (int) $row['annee'];
            if ($a >= 0 && $a <= 99) {
                $freqAnnee[$a] = (int) $row['cnt'];
            }
        }
    }

    $resSigne = $astroConn->query('SELECT signe, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY signe');
    if ($resSigne && $resSigne->num_rows > 0) {
        while ($row = $resSigne->fetch_assoc()) {
            $s = (int) $row['signe'];
            if ($s >= 1 && $s <= 12) {
                $signeStats[$s] = ['last_tirage' => $row['last_tirage']];
            }
        }
    }
    $sqlFreqSigne = "
        SELECT signe, COUNT(*) AS cnt
        FROM Astro_stats
        WHERE Tirage IN (
            SELECT Tirage FROM (SELECT DISTINCT Tirage FROM Astro_stats ORDER BY Tirage DESC" . $limitClause . ') AS t
        )
        GROUP BY signe
    ';
    $resFreqSigne = $astroConn->query($sqlFreqSigne);
    if ($resFreqSigne && $resFreqSigne->num_rows > 0) {
        while ($row = $resFreqSigne->fetch_assoc()) {
            $s = (int) $row['signe'];
            if ($s >= 1 && $s <= 12) {
                $freqSigne[$s] = (int) $row['cnt'];
            }
        }
    }
} else {
    if ($astroCount !== null) {
        $astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';
        $allowedRanges = [30, 100, 365];
        $countRangeParam = $_GET['count_range'] ?? '100';
        $countRange = ($countRangeParam === 'all') ? 'all' : (in_array((int) $countRangeParam, $allowedRanges, true) ? (int) $countRangeParam : 100);
    } else {
    $astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';
    $allowedRanges = [30, 100, 365];
    $countRangeParam = $_GET['count_range'] ?? '100';
    $countRange = ($countRangeParam === 'all') ? 'all' : (in_array((int) $countRangeParam, $allowedRanges, true) ? (int) $countRangeParam : 100);
    }
}

$astroConn->close();

function astro_days_since(?string $lastTirage, DateTime $today): string
{
    if ($lastTirage === null || $lastTirage === '') {
        return '';
    }
    try {
        return (string) $today->diff(new DateTime($lastTirage))->days;
    } catch (Throwable $e) {
        return '';
    }
}

/** Jours passés (2e colonne) → classe de ligne. ≤10 : pas de zébrage. */
function astro_stats_row_class(int $val): string
{
    if ($val <= 10) {
        return '';
    }
    if ($val <= 20) {
        return 'color-range-2';
    }
    if ($val <= 30) {
        return 'color-range-3';
    }
    return 'color-range-4';
}

$tableHTML = '';
$today = new DateTime('today');
foreach ($data as $row) {
    $tableHTML .= '<tr>';
    $tableHTML .= '<td>' . htmlspecialchars((string) ($row['Tirage'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string) ($row['jour'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
    $moisNum = (int) ($row['mois'] ?? 0);
    $moisLabel = ($moisNum >= 1 && $moisNum <= 12) ? t('astro.mois.' . $moisNum) : (string) ($row['mois'] ?? '');
    $tableHTML .= '<td>' . htmlspecialchars($moisLabel, ENT_QUOTES, 'UTF-8') . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string) ($row['annee'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
    $s = (int) ($row['signe'] ?? 0);
    $signe_display = isset($signe_symb[$s], $signe_abr[$s]) ? $signe_symb[$s] . ' ' . $signe_abr[$s] : (string) ($row['signe'] ?? '');
    $tableHTML .= '<td>' . htmlspecialchars($signe_display, ENT_QUOTES, 'UTF-8') . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string) ($row['fois'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string) ($row['days'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
    $tableHTML .= '</tr>';
}

$jourStatsHTML = '';
for ($j = 1; $j <= 31; $j++) {
    $joursPasses = '';
    if (isset($jourStats[$j])) {
        $joursPasses = astro_days_since((string) $jourStats[$j]['last_tirage'], $today);
    }
    $val = $joursPasses !== '' ? (int) $joursPasses : 0;
    $class = astro_stats_row_class($val);
    $count = $freqStats[$j] ?? 0;
    $jourStatsHTML .= $class !== '' ? '<tr class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' : '<tr>';
    $jourStatsHTML .= '<td>' . $j . '</td>';
    $jourStatsHTML .= '<td>' . htmlspecialchars($joursPasses, ENT_QUOTES, 'UTF-8') . '</td>';
    $jourStatsHTML .= '<td><span class="x-small">x</span>' . $count . '</td>';
    $jourStatsHTML .= '</tr>';
}

$moisStatsHTML = '';
for ($m = 1; $m <= 12; $m++) {
    $joursPassesM = '';
    if (isset($moisStats[$m])) {
        $joursPassesM = astro_days_since((string) $moisStats[$m]['last_tirage'], $today);
    }
    $valM = $joursPassesM !== '' ? (int) $joursPassesM : 0;
    $classM = astro_stats_row_class($valM);
    $countM = $freqMois[$m] ?? 0;
    $moisStatsHTML .= $classM !== '' ? '<tr class="' . htmlspecialchars($classM, ENT_QUOTES, 'UTF-8') . '">' : '<tr>';
    $moisStatsHTML .= '<td>' . htmlspecialchars(t('astro.mois.' . $m), ENT_QUOTES, 'UTF-8') . '</td>';
    $moisStatsHTML .= '<td>' . htmlspecialchars($joursPassesM, ENT_QUOTES, 'UTF-8') . '</td>';
    $moisStatsHTML .= '<td><span class="x-small">x</span>' . $countM . '</td>';
    $moisStatsHTML .= '</tr>';
}

$anneeStatsHTML = '';
for ($a = 0; $a <= 99; $a++) {
    $joursPassesA = '';
    if (isset($anneeStats[$a])) {
        $joursPassesA = astro_days_since((string) $anneeStats[$a]['last_tirage'], $today);
    }
    $valA = $joursPassesA !== '' ? (int) $joursPassesA : 0;
    $classA = astro_stats_row_class($valA);
    $countA = $freqAnnee[$a] ?? 0;
    $anneeStatsHTML .= $classA !== '' ? '<tr class="' . htmlspecialchars($classA, ENT_QUOTES, 'UTF-8') . '">' : '<tr>';
    $anneeStatsHTML .= '<td>' . sprintf('%02d', $a) . '</td>';
    $anneeStatsHTML .= '<td>' . htmlspecialchars($joursPassesA, ENT_QUOTES, 'UTF-8') . '</td>';
    $anneeStatsHTML .= '<td><span class="x-small">x</span>' . $countA . '</td>';
    $anneeStatsHTML .= '</tr>';
}

$signeStatsHTML = '';
for ($s = 1; $s <= 12; $s++) {
    $joursPassesS = '';
    if (isset($signeStats[$s])) {
        $joursPassesS = astro_days_since((string) $signeStats[$s]['last_tirage'], $today);
    }
    $valS = $joursPassesS !== '' ? (int) $joursPassesS : 0;
    $classS = astro_stats_row_class($valS);
    $countS = $freqSigne[$s] ?? 0;
    $signeDisplay = $signe_symb[$s] . ' ' . $signe_abr[$s];
    $signeStatsHTML .= $classS !== '' ? '<tr class="' . htmlspecialchars($classS, ENT_QUOTES, 'UTF-8') . '">' : '<tr>';
    $signeStatsHTML .= '<td>' . htmlspecialchars($signeDisplay, ENT_QUOTES, 'UTF-8') . '</td>';
    $signeStatsHTML .= '<td>' . htmlspecialchars($joursPassesS, ENT_QUOTES, 'UTF-8') . '</td>';
    $signeStatsHTML .= '<td><span class="x-small">x</span>' . $countS . '</td>';
    $signeStatsHTML .= '</tr>';
}

ob_start();
include __DIR__ . '/astro.html';
$template = ob_get_clean();

$template = str_replace('<!--TABLE_PLACEHOLDER-->', $tableHTML, $template);
$template = str_replace('<!--JOUR_STATS_PLACEHOLDER-->', $jourStatsHTML, $template);
$template = str_replace('<!--MOIS_STATS_PLACEHOLDER-->', $moisStatsHTML, $template);
$template = str_replace('<!--ANNEE_STATS_PLACEHOLDER-->', $anneeStatsHTML, $template);
$template = str_replace('<!--SIGNE_STATS_PLACEHOLDER-->', $signeStatsHTML, $template);
echo $template;
