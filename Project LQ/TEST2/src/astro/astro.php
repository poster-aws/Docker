<?php
require_once "db.php";

/* Справочники: в Astro_stats mois et signe sont numériques (1–12), on affiche en français */
$mois_fr = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
$signe_fr = [
    1 => 'Bélier', 2 => 'Taureau', 3 => 'Gémeaux', 4 => 'Cancer', 5 => 'Lion', 6 => 'Vierge',
    7 => 'Balance', 8 => 'Scorpion', 9 => 'Sagittaire', 10 => 'Capricorne', 11 => 'Verseau', 12 => 'Poissons'
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
    $tableHTML .= '<td>' . htmlspecialchars($signe_fr[(int)($row['signe'] ?? 0)] ?? (string)($row['signe'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['fois'] ?? '')) . '</td>';
    $tableHTML .= '<td>' . htmlspecialchars((string)($row['days'] ?? '')) . '</td>';
    $tableHTML .= '</tr>';
}

echo str_replace('<!--TABLE_PLACEHOLDER-->', $tableHTML, $template);
