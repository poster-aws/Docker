<?php
// Подключение к базе данных
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

// Создание соединения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// -------------------------------
// 1. Получаем все записи из Q2_stats_order (для левой таблицы)
$sql = "SELECT * FROM Q2_stats_order ORDER BY Tirage DESC";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// -------------------------------
// 2. Получаем последние появления отдельных чисел (на основе MAX(Tirage) по числам из n1 и n2)
$sql_last_appearance = "
    WITH All_Numbers AS (
        SELECT n1 AS n, Tirage FROM Q2_stats_order
        UNION ALL
        SELECT n2 AS n, Tirage FROM Q2_stats_order
    ),
    Last_Appearance AS (
        SELECT n, MAX(Tirage) AS Last_Date
        FROM All_Numbers
        GROUP BY n
    )
    SELECT 
        n AS Number, 
        DATEDIFF(CURDATE(), Last_Date) AS Days_Since_Last_Appearance
    FROM Last_Appearance
    ORDER BY Number;
";
$result_last_appearance = $conn->query($sql_last_appearance);

$last_appearance_data = [];
if ($result_last_appearance->num_rows > 0) {
    while ($row = $result_last_appearance->fetch_assoc()) {
        $last_appearance_data[] = $row;
    }
}

// -------------------------------
// 3. Упорядоченные комбинации (n1, n2)
$sql_ordered = "
    SELECT CONCAT(n1, n2) AS combination, MAX(Tirage) AS Last_Date
    FROM Q2_stats_order
    GROUP BY n1, n2
";
$result_ordered = $conn->query($sql_ordered);

$ordered_occurrences = [];
if ($result_ordered && $result_ordered->num_rows > 0) {
    while ($row = $result_ordered->fetch_assoc()) {
        $ordered_occurrences[$row['combination']] = (new DateTime($row['Last_Date']))->diff(new DateTime())->days;
    }
}

$ordered_combinations = [];
for ($i = 0; $i < 10; $i++) {
    for ($j = 0; $j < 10; $j++) {
        $comb = "$i$j";
        $days = isset($ordered_occurrences[$comb]) ? $ordered_occurrences[$comb] : null;
        $ordered_combinations[] = [
            'n1' => $i,
            'n2' => $j,
            'days' => $days
        ];
    }
}
usort($ordered_combinations, function($a, $b) {
    $a_val = ($a['days'] === null) ? 999999 : (int)$a['days'];
    $b_val = ($b['days'] === null) ? 999999 : (int)$b['days'];
    return $b_val <=> $a_val;
});

// -------------------------------
// 4. Неупорядоченные комбинации
$sql_unordered = "
    SELECT LEAST(n1, n2) AS a, GREATEST(n1, n2) AS b, MAX(Tirage) AS Last_Date
    FROM Q2_stats_order
    GROUP BY a, b
";
$result_unordered = $conn->query($sql_unordered);

$unordered_occurrences = [];
if ($result_unordered && $result_unordered->num_rows > 0) {
    while ($row = $result_unordered->fetch_assoc()) {
        $comb = $row['a'] . $row['b'];
        $unordered_occurrences[$comb] = (new DateTime($row['Last_Date']))->diff(new DateTime())->days;
    }
}

$unordered_combinations = [];
for ($i = 0; $i < 10; $i++) {
    for ($j = $i; $j < 10; $j++) {
        $comb = "$i$j";
        $days = isset($unordered_occurrences[$comb]) ? $unordered_occurrences[$comb] : null;
        $unordered_combinations[] = [
            'n1' => $i,
            'n2' => $j,
            'days' => $days
        ];
    }
}
usort($unordered_combinations, function($a, $b) {
    $a_val = ($a['days'] === null) ? 999999 : (int)$a['days'];
    $b_val = ($b['days'] === null) ? 999999 : (int)$b['days'];
    return $b_val <=> $a_val;
});

// -------------------------------
// IP-адрес пользователя
$ip = $_SERVER['REMOTE_ADDR'];

// Закрытие соединения
$conn->close();

// Подключение шаблона
include 'template.html';
?>