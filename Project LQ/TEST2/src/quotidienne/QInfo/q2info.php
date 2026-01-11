<?php
require_once "../db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --------------------
// 1) Режим переключателя (ORDER / N'import) для графика
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table = $isNorder ? "Q2_stats_norder" : "Q2_stats_order";

// 2) LIMIT для графика (как у тебя)
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 100;

// 3) GRID LIMIT (как в Q3info, отдельный параметр)
$allowedGridLimits = [50, 100, 365];
$gridLimit = isset($_GET['grid_limit']) && in_array((int)$_GET['grid_limit'], $allowedGridLimits, true)
    ? (int)$_GET['grid_limit']
    : 50;

// --------------------
// Получение данных для графика/таблицы диапазонов
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT n1, n2, days FROM $table ORDER BY Tirage DESC";
if ($limit > 0) {
    $sql .= " LIMIT $limit";
}

$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Если AJAX — возвращаем только данные (как у тебя)
if (isset($_GET['ajax'])) {
    $conn->close();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$json_data = json_encode($data, JSON_UNESCAPED_UNICODE);

// --------------------
// Получение данных для GRID (как Q3info, но Q2)
$sqlGrid = "SELECT Tirage, n1, n2 FROM Q2 ORDER BY Tirage DESC LIMIT $gridLimit";
$resGrid = $conn->query($sqlGrid);

$tirages = [];
if ($resGrid && $resGrid->num_rows > 0) {
    while ($r = $resGrid->fetch_assoc()) {
        $tirages[] = [
            'Tirage' => $r['Tirage'],
            'nums'   => [(int)$r['n1'], (int)$r['n2']]
        ];
    }
}

/* === ДОБАВЛЕНО: сумма выпадений цифр для GRID (с учетом дублей) === */
$digitSums = array_fill(0, 10, 0);
foreach ($tirages as $t) {
    foreach ($t['nums'] as $num) {
        $digitSums[$num]++;
    }
}
/* === /ДОБАВЛЕНО === */

$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="qinfo.css">   <!-- == Scroll hint (bouncing ball) == -->

  <style>
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: sans-serif;
      overflow-y: auto;
      scrollbar-width: none;
    }
    body::-webkit-scrollbar { display: none; }

    #toggleWrapper,
    #selectWrapper {
      text-align: center;
      margin: 8px 0;
    }

    #limitSelect, #norderToggle {
      font-size: 1em;
      margin-left: 6px;
    }
    label[for="norderToggle"] { margin-left: 8px; }

    #statsTable {
      margin: 10px auto;
      border-collapse: collapse;
      width: 60%;
    }
    #statsTable th, #statsTable td {
      border: 1px solid #999;
      padding: 6px 10px;
      text-align: center;
    }

    .circle {
      display: inline-block;
      width: 28px;
      height: 28px;
      line-height: 28px;
      border-radius: 50%;
      background-color: #7eb0ea;
      color: #000;
      font-weight: bold;
      text-align: center;
      font-family: Arial, sans-serif;
      margin: 0 3px;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
    }

    #infoBlock.info-list {
      display: flex;
      flex-direction: column;
      padding: 14px 16px;
      gap: 8px;
      font-size: 0.95em;
      max-width: 800px;
      margin: 30px auto;
      background: rgba(255,255,255,0.03);
      color: #333;
    }
    .info-row {
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 4px solid #FF8C00;
      padding-left: 10px;
      background: rgba(255, 255, 255, 0.26);
      border-radius: 6px;
    }
    .info-text { font-size: 0.95em; }

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

    .table-wrapper {
      width: 95%;
      max-height: 70vh;
      overflow: auto;
      margin: 0 auto;
      border: 1px solid #ccc;
      background: rgba(173, 216, 230, 0.85);
    }

    table.digit-grid {
      width: max-content;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 12px;
    }

    .digit-grid td, .digit-grid th {
      width: 20px;
      height: 20px;
      text-align: center;
      border: 1px solid #ccc;
      padding: 0;
      box-sizing: border-box;
    }

    .digit-grid th {
      height: 60px;
      writing-mode: vertical-rl;
      transform: rotate(180deg);
      font-size: 0.7em;
      background: #eee;
    }

    .digit-grid td.hit { background-color: #7eb0ea; }
    .digit-grid td.repeat-2 { background-color: #f8c471; }

    /* первый столбец (Σ) — липкий слева */
    .digit-grid td:first-child,
    .digit-grid th:first-child {
      background-color: #eee;
      position: sticky;
      left: 0;
      z-index: 1;
      font-weight: bold;
      color: #1f4fd8
    }

    /* второй столбец (#) */
    .digit-grid td:nth-child(2),
    .digit-grid th:nth-child(2) {
      background-color: #eee;
      font-weight: bold;
      
    }

    .filter-form {
      text-align: center;
      margin: 0;
      padding: 10px 0;
    }

    .filter-form select {
      font-size: 1em;
      border-radius: 6px;
      font-size: 1em;           /* меньше, чем 16px */
      padding: 2px 6px;         /* компактнее */
      line-height: 1.2;
    }

  </style>
</head>

<body>

  <div class="table-wrapper">
    <?php if (!empty($tirages)): ?>
      <table class="digit-grid">
        <thead>
          <tr>
          <th>Σ</th>  
          <th>#</th>
          <?php foreach ($tirages as $t): ?>
          <th><?= htmlspecialchars($t['Tirage']) ?></th>
          <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php for ($digit = 0; $digit <= 9; $digit++): ?>
            <tr>
                <!-- Σ слева -->
                <td>&nbsp;<?= $digitSums[$digit] ?>x&nbsp;</td> <!-- --тут Х -->
                <!-- # -->
                <td><?= $digit ?></td>
                <!-- столбцы тиражей -->
              <?php foreach ($tirages as $t):
                $count = array_count_values($t['nums'])[$digit] ?? 0;
                $class = ($count === 2) ? 'repeat-2' : (($count === 1) ? 'hit' : '');
              ?>
                <td class="<?= $class ?>"><?= $count > 0 ? $digit : '' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; color: red;">Нет данных для отображения.</p>
    <?php endif; ?>
  </div>

  <form class="filter-form" method="get">
    <input type="hidden" name="limit" value="<?= htmlspecialchars($limit) ?>">
    <?php if ($isNorder): ?>
      <input type="hidden" name="norder" value="1">
    <?php endif; ?>

    Dernières
    <select name="grid_limit" onchange="this.form.submit()">
      <?php foreach ([50, 100, 365] as $opt): ?>
        <option value="<?= $opt ?>" <?= ($gridLimit == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select> tirages
  </form>

  <canvas id="myChart" width="400" height="200"></canvas>

  <div id="toggleWrapper">
    <input type="checkbox" id="norderToggle" <?= $isNorder ? 'checked' : '' ?>>
    <label for="norderToggle">Dans N'Importe quel Order</label>
  </div>

  <div id="selectWrapper">
    <label for="limitSelect">Nombre de dernières tirages:</label>
    <select id="limitSelect">
      <option value="100" <?= ($limit == 100 ? 'selected' : '') ?>>100</option>
      <option value="200" <?= ($limit == 200 ? 'selected' : '') ?>>200</option>
      <option value="500" <?= ($limit == 500 ? 'selected' : '') ?>>500</option>
      <option value="1000" <?= ($limit == 1000 ? 'selected' : '') ?>>1000</option>
      <option value="0" <?= ($limit == 0 ? 'selected' : '') ?>>Tout</option>
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

  <div id="infoBlock" class="info-list">
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">1</span>
        <span class="circle">2</span>
      </div>
      <div class="info-text">Dans l'Order - Toutes les combinaisons : <b>100</b></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">2</span>
        <span class="circle">1</span>
      </div>
      <div class="info-text">N'Importe quel Order - Sans doublons : <b>45</b></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">0</span>
        <span class="circle">0</span>
      </div>
      <div class="info-text">Doublons : <b>10</b></div>
    </div>
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
        const percent = total > 0 ? ((count / total) * 100).toFixed(1) : '0.0';
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
        const response = await fetch(`q2info.php?limit=${limit}<?= $gridLimit ? "&grid_limit=" . (int)$gridLimit : "" ?>${isNorder ? '&norder=1' : ''}&ajax=1`);
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

    renderChart(<?php echo $json_data; ?>);
  </script>

  <div id="scrollHint">⬇⬆</div>

</body>
</html>