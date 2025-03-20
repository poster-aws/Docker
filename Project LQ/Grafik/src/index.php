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

// SQL-запрос для получения всех данных из таблицы Q2
$sql = "SELECT * FROM Q2 ORDER BY Tirage DESC";
$result = $conn->query($sql);

// Преобразуем результат запроса в массив
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Новый SQL-запрос (WITH Last_Appearance) для поиска последнего появления чисел
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

// Получаем IP-адрес пользователя
$ip = $_SERVER['REMOTE_ADDR'];

// Закрываем соединение с БД
$conn->close();

// Подключаем HTML-шаблон
include 'template.html';
?>