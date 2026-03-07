<?php
require_once "db.php";

/* Количество записей для заголовка */
$countResult = $astroConn->query("SELECT COUNT(*) AS total FROM Astro_stats");
$astroCount = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $astroCount = (int)$row['total'];
}

/* Таблица: 365 последних записей из Astro_stats (Tirage, jour, mois, annee, signe, days, fois) */
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
    foreach (['Tirage', 'jour', 'mois', 'annee', 'signe', 'fois', 'days'] as $key) {
        $val = $row[$key] ?? '';
        $tableHTML .= '<td>' . htmlspecialchars((string)$val) . '</td>';
    }
    $tableHTML .= '</tr>';
}

echo str_replace('<!--TABLE_PLACEHOLDER-->', $tableHTML, $template);
