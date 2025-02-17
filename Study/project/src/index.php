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
// Эту переменную вы можете использовать прямо в JavaScript
$json_data = json_encode($data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart Example</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>График данных</h1>
    <canvas id="myChart" width="400" height="200"></canvas>
    
    <script>
        // Данные, полученные из PHP (JSON)
        const dataFromPHP = <?php echo $json_data; ?>;
        
        // Извлекаем даты и значения из данных
        const labels = dataFromPHP.map(item => item.date);
        const values = dataFromPHP.map(item => item.value);

        // Создание графика
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'line', // Тип графика (линейный)
            data: {
                labels: labels, // Даты на оси X
                datasets: [{
                    label: 'Значение',
                    data: values, // Значения на оси Y
                    borderColor: 'rgba(75, 192, 192, 1)', // Цвет линии графика
                    borderWidth: 1,
                    fill: false // Без заливки графика
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true // Начинать график с 0
                    }
                }
            }
        });
    </script>
</body>
</html>
