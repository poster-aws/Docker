<?php
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT n1, n2, days FROM Q2_stats_order";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
$conn->close();

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
  <canvas id="myChart" width="400" height="200"></canvas>

  <script>
    let rawData = '<?php echo addslashes($json_data ?: '[]'); ?>';

    try {
      var dataFromPHP = JSON.parse(rawData);
    } catch (e) {
      console.error("Ошибка парсинга JSON:", e);
      var dataFromPHP = [];
    }

    // Корректное форматирование комбинаций от "00" до "99"
    const scatterData = dataFromPHP
      .map(item => ({
        x: parseInt(item.days) || 0,
        y: `${item.n1}${item.n2}`.padStart(2, "0")
      }))
      .filter(point => !isNaN(point.x));

    if (scatterData.length === 0) {
      scatterData.push({ x: 0, y: "00" });
    }

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

    const config = {
      type: 'scatter',
      data: data,
      options: {
        responsive: true,
        scales: {
          x: {
            title: {
              display: true,
              text: 'Jours passés'
            }
          },
          y: {
            ticks: {
              callback: function(value) {
                return uniqueYValues[value] || '';
              }
            },
            title: {
              display: true,
              text: 'Combinaisons'
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const index = context.raw.y;
                const combo = uniqueYValues[index] || '';
                return `Combinaison: ${combo}, Jours: ${context.raw.x}`;
              }
            }
          }
        }
      }
    };

    new Chart(document.getElementById('myChart'), config);
  </script>
</body>
</html>