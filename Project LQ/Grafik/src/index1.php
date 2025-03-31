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
// 1. Получаем все записи из таблицы Q2 (для левой таблицы)
$sql = "SELECT * FROM Q2 ORDER BY Tirage DESC";
$result = $conn->query($sql);

// Преобразуем результат запроса в массив
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// -------------------------------
// 2. Запрос для получения последних появления отдельных чисел (с правой таблицы)
$sql_last_appearance = "
    WITH Last_Appearance AS (
        SELECT n, MAX(Tirage) AS Last_Date
        FROM (
            SELECT n1 AS n, Tirage FROM Q2
            UNION ALL
            SELECT n2 AS n, Tirage FROM Q2
        ) AS numbers
        GROUP BY n
    )
    SELECT 
        n AS Number, 
        DATEDIFF(CURDATE(), Last_Date) AS Days_Since_Last_Appearance
    FROM Last_Appearance
    ORDER BY Number;
";
$result_last_appearance = $conn->query($sql_last_appearance);

// Преобразуем результат запроса в массив
$last_appearance_data = [];
if ($result_last_appearance->num_rows > 0) {
    while ($row = $result_last_appearance->fetch_assoc()) {
        $last_appearance_data[] = $row;
    }
}

// -------------------------------
// 3. Упорядоченные комбинации
$sql_ordered = "
    SELECT CONCAT(n1, n2) as combination, DATEDIFF(CURDATE(), MAX(Tirage)) as days_since
    FROM Q2
    GROUP BY combination
";
$result_ordered = $conn->query($sql_ordered);
$ordered_occurrences = [];
if ($result_ordered && $result_ordered->num_rows > 0) {
    while ($row = $result_ordered->fetch_assoc()) {
        $ordered_occurrences[$row['combination']] = $row['days_since'];
    }
}

// Обновлённая структура для ordered
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
    SELECT CONCAT(LEAST(n1, n2), GREATEST(n1, n2)) as combination, DATEDIFF(CURDATE(), MAX(Tirage)) as days_since
    FROM Q2
    GROUP BY combination
";
$result_unordered = $conn->query($sql_unordered);
$unordered_occurrences = [];
if ($result_unordered && $result_unordered->num_rows > 0) {
    while ($row = $result_unordered->fetch_assoc()) {
        $unordered_occurrences[$row['combination']] = $row['days_since'];
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
// Получаем IP-адрес пользователя
$ip = $_SERVER['REMOTE_ADDR'];

// Закрываем соединение с БД
$conn->close();

// Подключаем HTML-шаблон
include 'xlam.html';
?>