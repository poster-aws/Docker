<?php
// Подключение к базе данных
$servername = "db";  // Имя сервера базы данных
$username = "user";  // Имя пользователя базы данных
$password = "user";  // Пароль пользователя базы данных
$dbname = "quotidienne2";  // Название базы данных

// Создание соединения с MySQL
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка соединения на наличие ошибок
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// SQL-запрос для получения всех данных из таблицы Q2, отсортированных по полю Tirage (по убыванию)
$sql = "SELECT * FROM Q2 ORDER BY Tirage DESC";
$result = $conn->query($sql);

// Закрываем соединение с базой данных после выполнения запроса
$conn->close();

// Преобразуем результат запроса в массив
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row; // Добавляем каждую строку в массив
    }
}

// Получаем IP-адрес пользователя
$ip = $_SERVER['REMOTE_ADDR'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ТЕСТОВАЯ СТРАНИЦА -=^..^=-</title>
    <style>
body {
    background-color: lightblue; /* Цвет фона */
    font-family: Arial, sans-serif;
    text-align: center;
    margin-top: 50px;
        }
.bottom-right {
    position: fixed;
    bottom: 10px;
    right: 10px;
    color: blue;
    margin-top: 50px;
    padding: 10px;
    border-radius: 5px;
}

@import url('https://fonts.googleapis.com/css2?family=Shadows+Into+Light&display=swap');

/* Контейнер таблицы (мятая бумага + тетрадные линии) */
.table-container {
    position: fixed;
    top: 10px; /* Отступ сверху */
    left: 10px; /* Размещение в левом верхнем углу */
    width: 180px; /* Фиксированная ширина */
    max-height: 90vh; /* Ограничение высоты */
    overflow: auto; /* Позволяет прокручивать содержимое */
    
    background: url('https://www.transparenttextures.com/patterns/crumpled-paper.png'), 
                linear-gradient(80deg, #fdf7c3 10%,rgb(240, 204, 43) 100%); /* Мятая бумага + градиент */
    background-size: cover; /* Покрывает весь фон */

    padding: 15px;
    border-radius: 12px; /* Закруглённые углы */
    box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.4); /* Тень для объёма */
    filter: drop-shadow(2px 4px 6px rgba(0, 0, 0, 0.3)); /* Глубина */
    
    border: 2px solid #e3c77b; /* Лёгкая рамка */
}
/* Таблица (рукописный стиль) */
.interactive-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 18px;
    font-family: 'Shadows Into Light', cursive;/* Рукописный стиль */
    color: #1a237e; /* Тёмно-синий цвет текста, как чернила */
}

/* Вертикальная прокрутка без видимого ползунка */
.interactive-table tbody {
    display: block;
    max-height: 70vh; /* Ограничение высоты */
    overflow-y: auto; /* Вертикальная прокрутка */
    overflow-x: hidden; /* Отключаем горизонтальную прокрутку */
    
    scrollbar-width: none; /* Скрываем ползунок в Firefox */
}

/* Скрываем ползунок в Chrome, Safari и Edge */
.interactive-table tbody::-webkit-scrollbar {
    display: none;
}

/* Ячейки таблицы */
.interactive-table td {
    border-bottom: 1px dashed #8d6e63; /* Пунктирные линии, как в тетради */
    padding: 10px;
    text-align: left;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Подсветка строки при наведении */
.interactive-table tr:hover {
    background-color: rgba(255, 255, 255, 0.3);
    transition: background-color 0.3s ease;
}

/* Подсветка ячейки при наведении */
.interactive-table td:hover {
    background-color: rgba(255, 255, 255, 0.5);
}
        
    </style>
</head>
<body>
    <h1>Вся страница</h1>
    <div id="time" aria-live="polite">Загрузка времени...</div>
    <div id="ip">Ваш IP-адрес: <?= htmlspecialchars($ip) ?></div>
    <p><a href="days.php">Перейти на страницу дней</a></p>

    <!-- Контейнер таблицы (верхний правый угол) -->
    <div class="table-container">
        <table class="interactive-table">
            <tbody>
                <?php
                if (!empty($data)) {
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $cell) {
                            echo "<td>" . htmlspecialchars($cell) . "</td>";
                        }
                        echo "</tr>";
                    }
                } else {
                    // Если в таблице нет данных, выводится сообщение
                    echo "<tr><td colspan='100%'>Нет данных</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="bottom-right"><b>PosteR</b></div>

    <script>
        // Функция для обновления текущего времени
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString(); // Получение текущего времени
            document.getElementById('time').textContent = 'Текущее время: ' + timeString;
        }

        // Обновляем время каждую секунду
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>