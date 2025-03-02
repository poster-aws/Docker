<?php
$servername = "db"; // Имя сервиса в Docker Compose
$username = "user";
$password = "q";
$dbname = "quotidienne2";

// Включаем отображение ошибок (на время разработки)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Создаем подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Устанавливаем кодировку UTF-8
$conn->set_charset("utf8");

// Проверка соединения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Выполняем запрос из таблицы Q2_days
$sql = "SELECT n1, n2, days FROM Q2_days";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Закрываем соединение
$conn->close();

// Преобразуем данные в формат JSON (без Unicode-экранирования)
$json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="ru">
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
        // Получаем данные из PHP (JSON)
        let rawData = '<?php echo addslashes($json_data ?: '[]'); ?>'; // Экранируем JSON
        console.log("Raw JSON Data:", rawData);

        try {
            var dataFromPHP = JSON.parse(rawData);
        } catch (e) {
            console.error("Ошибка парсинга JSON:", e);
            var dataFromPHP = [];
        }

        // Проверка наличия данных
        if (!Array.isArray(dataFromPHP) || dataFromPHP.length === 0) {
            console.warn("Нет данных для отображения");
        } else {
            console.log("Данные получены:", dataFromPHP);
        }

        // Преобразуем данные в формат x = days, y = "n1n2"
        const scatterData = dataFromPHP
            .map(item => ({
                x: parseInt(item.days) || 0, // Значения по оси X (days), по умолчанию 0
                y: `${item.n1}${item.n2}` // Форматируем n1 и n2 как строку (например, "01", "10")
            }))
            .filter(point => !isNaN(point.x) && /^[0-9]{2}$/.test(point.y)); // Проверяем корректность данных

        console.log("Форматированные данные:", scatterData);

        // Если данных нет, добавляем заглушку
        if (scatterData.length === 0) {
            scatterData.push({ x: 0, y: "00" });
        }

        // Определяем уникальные y-значения и создаем индекс для оси Y
        const uniqueYValues = [...new Set(scatterData.map(p => p.y))].sort();
        const yIndexMap = Object.fromEntries(uniqueYValues.map((val, index) => [val, index]));

        // Преобразуем y в числовые индексы
        scatterData.forEach(point => {
            point.y = yIndexMap[point.y];
        });

        // Создание графика
        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Значение',
                    data: scatterData,
                    backgroundColor: 'rgba(75, 192, 6, 1)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    showLine: false,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Days'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'n1n2'
                        },
                        ticks: {
                            callback: function(value, index, values) {
                                return uniqueYValues[index]; // Отображаем оригинальные значения n1n2
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
