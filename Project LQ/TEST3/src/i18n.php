<?php

$lang = isset($_GET['lang']) ? strtolower((string)$_GET['lang']) : 'fr';
if (!in_array($lang, ['fr', 'en'], true)) {
    $lang = 'fr';
}

$I18N = [
    'fr' => [
        'q2.col.draw' => "Tirage",
        'q2.col.last_365' => "365 dernières",
        'q2.col.days_passed' => "Jours<br>passés",
        'q2.col.prev_draw' => "L'avant<br>dernière",
        'q2.col.times' => "Fois",
        'q2.col.max_days_passed' => "Max jours<br>passés",
        'q2.col.last_draw' => "Dernier<br>Tirage",
        'q2.label.draws' => "Tirages",
        'q2info.no_data' => "Pas de données à afficher.",
        'q2info.latest' => "Derniers",
        'q2info.draws_suffix' => "tirages",
        'q2info.any_order' => "Dans N'importe quel ordre",
        'q2info.last_draw_count' => "Nombre de derniers tirages:",
        'q2info.all' => "Tout",
        'q2info.stats.days' => "Jours passés",
        'q2info.stats.combo_count' => "Nombre de combinaisons",
        'q2info.chart.combinations' => "Combinaisons",
        'q2info.chart.tooltip' => "Combinaison: {combo}, Jours: {days}",
        'q2info.info.all_combinations' => "Dans l'Order - Toutes les combinaisons : <b>100</b>",
        'q2info.info.any_order_no_duplicates' => "N'Importe quel Order - Sans doublons : <b>45</b>",
        'q2info.info.duplicates' => "Doublons : <b>10</b>",
    ],
    'en' => [
        'q2.col.draw' => "Draw",
        'q2.col.last_365' => "Last 365",
        'q2.col.days_passed' => "Days<br>ago",
        'q2.col.prev_draw' => "Previous<br>draw",
        'q2.col.times' => "Times",
        'q2.col.max_days_passed' => "Max days<br>ago",
        'q2.col.last_draw' => "Last<br>draw",
        'q2.label.draws' => "Draws",
        'q2info.no_data' => "No data to display.",
        'q2info.latest' => "Last",
        'q2info.draws_suffix' => "draws",
        'q2info.any_order' => "In any order",
        'q2info.last_draw_count' => "Number of latest draws:",
        'q2info.all' => "All",
        'q2info.stats.days' => "Days ago",
        'q2info.stats.combo_count' => "Number of combinations",
        'q2info.chart.combinations' => "Combinations",
        'q2info.chart.tooltip' => "Combination: {combo}, Days: {days}",
        'q2info.info.all_combinations' => "In order - All combinations: <b>100</b>",
        'q2info.info.any_order_no_duplicates' => "In any order - No duplicates: <b>45</b>",
        'q2info.info.duplicates' => "Duplicates: <b>10</b>",
    ],
];

if (!function_exists('t')) {
    function t(string $key): string
    {
        global $I18N, $lang;
        return $I18N[$lang][$key] ?? $I18N['fr'][$key] ?? $key;
    }
}

