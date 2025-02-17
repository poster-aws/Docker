<?php
$servername = "db"; // Имя сервиса в Docker Compose
$username = "user";
$password = "password";
$dbname = "test_db";

// Создаем подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Выполняем запрос
$sql = "SELECT date, value FROM data_table";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    // Извлекаем данные
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo "0 results";
}

$conn->close();

// Преобразуем данные в формат JSON
echo json_encode($data);
?>
