<?php
require_once "../db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// выбор таблицы
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table = $isNorder ? "Q2_stats_norder" : "Q2_stats_order";

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 100;
$sql = "SELECT n1, n2, days FROM $table ORDER BY Tirage DESC";
if ($limit > 0) {
    $sql .= " LIMIT $limit";
}

$result = $conn->query($sql);
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
$conn->close();

if (isset($_GET['ajax'])) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- <title>График данных</title> -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>

/* Убираем отступы по умолчанию и задаем шрифт всей странице */
html, body {
  margin: 0;
  padding: 0;
  font-family: sans-serif;
}

/* Заголовок h2 по центру с уменьшенными вертикальными отступами */
h2 {
  text-align: center;
  margin: 8px 0;
  font-size: 1.1em; /* немного уменьшенный размер шрифта */
}

/* Центрирование и уменьшение вертикальных отступов обёрток переключателя и селектора */
#toggleWrapper,
#selectWrapper {
  text-align: center;
  margin: 8px 0;
}

/* Стиль селектора и чекбокса: шрифт и отступ слева */
#limitSelect, #norderToggle {
  font-size: 1em;
  margin-left: 6px;
}

/* Дополнительный отступ между чекбоксом и его подписью */
label[for="norderToggle"] {
  margin-left: 8px;
}

/* Основная таблица: по центру, с ограниченной шириной и сжатым вертикальным отступом */
#statsTable {
  margin: 10px auto;
  border-collapse: collapse;
  width: 60%;
}

/* Ячейки таблицы: рамка, отступы внутри ячеек и выравнивание по центру */
#statsTable th, #statsTable td {
  border: 1px solid #999;
  padding: 6px 10px;
  text-align: center;
}

/* Информационный блок под таблицей: ограниченная ширина, серый фон и цветной бордер */
#infoBlock {
  max-width: 800px;
  margin: 14px auto;
  padding: 8px 16px;
  background:rgba(245, 245, 245, 0);
  border-left: 4px solid #007BFF;
  font-size: 0.95em;
  line-height: 1.3;
  color: #333;
}

  .digit {
    display: inline-flex;
    width: 20px;
    height: 20px;
    margin-right: 5px;
    border-radius: 50%;
    background-color: #7eb0ea;
    color: #000;
    font-weight: bold;
    justify-content: center;
    align-items: center;
    text-align: center;
    font-family: Arial, sans-serif;
    box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);

  }
      /* Скрытие полос прокрутки */
  body {
    overflow: auto;
    scrollbar-width: none; /* Firefox */
  }

  body::-webkit-scrollbar {
    display: none;
  }

  </style>
</head>

<body>
  <h2>График количества дней с последнего появления комбинаций</h2>
  <canvas id="myChart" width="400" height="200"></canvas>

  <div id="toggleWrapper">
    <input type="checkbox" id="norderToggle">
    <label for="norderToggle">Dans N'importe quel order</label>
  </div>

  <div id="selectWrapper">
    <label for="limitSelect">Nombre de dernières éditions:</label>
    <select id="limitSelect">
      <option value="100" selected>100</option>
      <option value="200">200</option>
      <option value="500">500</option>
      <option value="1000">1000</option>
      <option value="0">Tout</option>
    </select>
  </div>

  <table id="statsTable">
    <thead>
      <tr>
        <th>Jours passé</th>
        <th>Nombre de combinaisons</th>
        <th>%</th>
      </tr>
    </thead>
    <tbody id="statsBody"></tbody>
  </table>

  <!-- Информационный блок -->
<div id="infoBlock">
  <span class="digit">1</span>
  <span class="digit">2</span>
  Nombre de combinaisons dans Order – 100<br>
  <span class="digit">2</span>
  <span class="digit">1</span>
  Nombre de combinaisons dans N'importe quel order – 55<br>
  <span class="digit">0</span>
  <span class="digit">0</span>
  Nombre de combinaisons "Doublons" – 10
</div>


  <script>
    let chart;
    const ctx = document.getElementById('myChart');

    function formatData(dataFromPHP) {
      const scatterData = dataFromPHP.map(item => ({
        x: parseInt(item.days) || 0,
        y: `${item.n1}${item.n2}`.padStart(2, "0")
      })).filter(point => !isNaN(point.x));

      const uniqueYValues = [...new Set(scatterData.map(p => p.y))].sort();
      const yIndexMap = Object.fromEntries(uniqueYValues.map((val, idx) => [val, idx]));

      const data = {
        datasets: [{
          label: 'Combinaisons',
          data: scatterData.map(point => ({
            x: point.x,
            y: yIndexMap[point.y]
          })),
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
        }]
      };

      return { config: {
        type: 'scatter',
        data: data,
        options: {
          responsive: true,
          scales: {
            x: { title: { display: true, text: 'Jours passés' } },
            y: {
              ticks: {
                callback: value => uniqueYValues[value] || ''
              },
              title: { display: true, text: 'Combinaisons' }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: context => {
                  const index = context.raw.y;
                  const combo = uniqueYValues[index] || '';
                  return `Combinaison: ${combo}, Jours: ${context.raw.x}`;
                }
              }
            }
          }
        }
      }, comboDays: scatterData };
    }

    function calculateStats(comboDays) {
      const ranges = {
        '1–50': 0,
        '1–100': 0,
        '1–200': 0,
        '201+': 0
      };

      comboDays.forEach(item => {
        const days = item.x;
        if (days >= 1 && days <= 50) ranges['1–50']++;
        if (days >= 1 && days <= 100) ranges['1–100']++;
        if (days >= 1 && days <= 200) ranges['1–200']++;
        if (days >= 201) ranges['201+']++;
      });

      const total = comboDays.length;
      const body = document.getElementById('statsBody');
      body.innerHTML = '';
      for (const [label, count] of Object.entries(ranges)) {
        const percent = ((count / total) * 100).toFixed(1);
        body.innerHTML += `<tr><td>${label}</td><td>${count}</td><td>${percent}%</td></tr>`;
      }
    }

    function renderChart(data) {
      const { config, comboDays } = formatData(data);
      if (chart) chart.destroy();
      chart = new Chart(ctx, config);
      calculateStats(comboDays);
    }

    async function loadData(limit, isNorder) {
      try {
        const response = await fetch(`q2info.php?limit=${limit}${isNorder ? '&norder=1' : ''}&ajax=1`);
        const data = await response.json();
        renderChart(data);
      } catch (error) {
        console.error("Ошибка загрузки данных:", error);
      }
    }

    function getLimit() {
      return document.getElementById('limitSelect').value;
    }

    function getNorder() {
      return document.getElementById('norderToggle').checked;
    }

    document.getElementById('limitSelect').addEventListener('change', function () {
      loadData(getLimit(), getNorder());
    });

    document.getElementById('norderToggle').addEventListener('change', function () {
      loadData(getLimit(), getNorder());
    });

    // initial render
    renderChart(<?php echo $json_data; ?>);
  </script>

</body>
</html>