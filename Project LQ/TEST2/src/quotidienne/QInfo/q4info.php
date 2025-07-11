<?php
require_once "../db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Вспомогательные функции
function isUniqueCombo($n1, $n2, $n3, $n4) {
    return count(array_unique([$n1, $n2, $n3, $n4])) === 4;
}

function getComboType($nums) {
    $count = array_count_values($nums);
    $values = array_values($count);
    rsort($values);

    if (count($count) === 4) return 'unique';
    if (count($count) === 3) return 'onepair';
    if (count($count) === 2 && in_array(2, $values) && $values[0] === 2) return 'twopairs';
    if ($values[0] === 3) return 'triplet';
    return 'other';
}

// Получаем данные из таблицы Q4_fois
$fois1      = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 1");
$freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0");
$freeNorder = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0 and n1=n2 and n2=n3 and n3=n4");

// Загружаем шаблон
ob_start();
include "q4info.html";
$template = ob_get_clean();

// Генерация HTML для каждой таблицы
function generateTableRows($result) {
    $html = '';
    while ($row = $result->fetch_assoc()) {
        $comboType = getComboType([$row['n1'], $row['n2'], $row['n3'], $row['n4']]);
        $highlight = isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '';
        $html .= "<tr data-combo-type=\"$comboType\" class=\"$highlight\">";
    $html .= "<td>";
    foreach (['n1', 'n2', 'n3', 'n4'] as $k) {
        $html .= "<span class=\"circle\">{$row[$k]}</span>";
    }
    $html .= "</td></tr>";
    }
    return $html;
}

$tableFois1 = generateTableRows($fois1);
$tableFois0 = generateTableRows($freeOrder);
$tableNorder = generateTableRows($freeNorder);

// Вставляем в шаблон
echo str_replace(
    ['<!--FOIS1_PLACEHOLDER-->', '<!--FOIS0_PLACEHOLDER-->', '<!--NORDER_PLACEHOLDER-->'],
    [$tableFois1, $tableFois0, $tableNorder],
    $template
);

$conn->close();