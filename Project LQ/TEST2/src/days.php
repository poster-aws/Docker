<?php
require_once "db.php";

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
  <title>График данных</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    #limitSelect, #norderToggle {
      font-size: 1em;
      margin-top: 10px;
    }
    #statsTable {
      margin-top: 20px;
      border-collapse: collapse;
      width: 60%;
      margin-left: auto;
      margin-right: auto;
    }
    #statsTable th, #statsTable td {
      border: 1px solid #999;
      padding: 8px 12px;
      text-align: center;
    }
    #selectWrapper {
      text-align: center;
      margin-top: 20px;
    }
    h2 {
      text-align: center;
      margin-bottom: 10px;
    }
    #toggleWrapper {
      text-align: center;
      margin-top: 10px;
    }
    label[for="norderToggle"] {
      margin-left: 8px;
    }
  </style>
</head>
<body>
  <h2>График количества дней с последнего появления комбинаций</h2>
  <canvas id="myChart" width="400" height="200"></canvas>

  <div id="toggleWrapper">
    <input type="checkbox" id="norderToggle">
    <label for="norderToggle">Без учёта порядка (norder)</label>
  </div>

  <div id="selectWrapper">
    <label for="limitSelect">Показать последние:</label>
    <select id="limitSelect">
      <option value="100" selected>100</option>
      <option value="200">200</option>
      <option value="500">500</option>
      <option value="1000">1000</option>
      <option value="0">Все</option>
    </select>
  </div>

  <table id="statsTable">
    <thead>
      <tr>
        <th>Диапазон дней</th>
        <th>Кол-во комбинаций</th>
        <th>% от общего</th>
      </tr>
    </thead>
    <tbody id="statsBody"></tbody>
  </table>

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
        const response = await fetch(`days.php?limit=${limit}${isNorder ? '&norder=1' : ''}&ajax=1`);
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