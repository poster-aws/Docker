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
    ],
];

if (!function_exists('t')) {
    function t(string $key): string
    {
        global $I18N, $lang;
        return $I18N[$lang][$key] ?? $I18N['fr'][$key] ?? $key;
    }
}

