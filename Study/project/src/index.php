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
$sql = "SELECT two_char, three_char FROM data_table";
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
    <title>График данных</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>График данных</h1>
    <canvas id="myChart" width="400" height="200"></canvas>
    
    <script>
        // Данные, полученные из PHP (JSON)
        const dataFromPHP = <?php echo $json_data; ?>;
        
        // Проверка данных
        console.log(dataFromPHP);  // Это поможет проверить, что данные приходят в JavaScript

        // Извлекаем два символа и три символа из данных
        const labels = dataFromPHP.map(item => parseInt(item.two_char));  // Два символа как числа для оси Y
        const values = dataFromPHP.map(item => parseInt(item.three_char));  // Три символа как числа для оси X

        // Создание графика
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'scatter', // Тип графика - точечный (scatter)
            data: {
                datasets: [{
                    label: 'Значение',
                    data: dataFromPHP.map((item, index) => ({
                        x: parseInt(item.three_char),  // Значения по оси X (three_char)
                        y: parseInt(item.two_char)    // Значения по оси Y (two_char)
                    })),
                    backgroundColor: 'rgba(75, 192, 6, 1)', // Цвет точек
                    borderColor: 'rgba(75, 192, 192, 1)', // Цвет обводки точек
                    borderWidth: 1, // Толщина обводки
                    pointRadius: 4, // Радиус точек
                    pointHoverRadius: 5, // Радиус точек при наведении
                    showLine: false, // Отключаем отображение линии
                    fill: false, // Отключаем заливку
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        min: 1,  // Минимальное значение на оси X (1)
                        max: 200, // Максимальное значение на оси X (200)
                        title: {
                            display: true,
                            text: 'Три символа (значение)'
                        }
                    },
                    y: {
                        min: 1,  // Минимальное значение на оси Y (1)
                        max: 100, // Максимальное значение на оси Y (100)
                        title: {
                            display: true,
                            text: 'Два символа'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
