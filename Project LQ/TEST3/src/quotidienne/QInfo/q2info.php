<?php
require_once "../db.php";
require_once __DIR__ . "/../../i18n.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table = $isNorder ? "Q2_stats_norder" : "Q2_stats_order";

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 100;

$allowedGridLimits = [50, 100, 365];
$gridLimit = isset($_GET['grid_limit']) && in_array((int)$_GET['grid_limit'], $allowedGridLimits, true)
    ? (int)$_GET['grid_limit']
    : 50;

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

if (isset($_GET['ajax'])) {
    $conn->close();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
$jsTexts = json_encode([
    'chartCombinations' => t('q2info.chart.combinations'),
    'statsDays' => t('q2info.stats.days'),
    'statsComboCount' => t('q2info.stats.combo_count'),
    'tooltipPattern' => t('q2info.chart.tooltip'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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

$digitSums = array_fill(0, 10, 0);
foreach ($tirages as $t) {
    foreach ($t['nums'] as $num) {
        $digitSums[$num]++;
    }
}

$q2count = 0;
$countResult = $conn->query("SELECT COUNT(*) as total FROM Q2");
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q2count = (int)$row['total'];
}

$conn->close();

$q2infoBoot = [
    'texts' => json_decode($jsTexts, true),
    'initialData' => json_decode($json_data, true),
];
$bootJson = json_encode($q2infoBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<div id="q2-meta" data-count="<?= (int)$q2count ?>"></div>
<link rel="stylesheet" href="quotidienne/QInfo/qinfo.css">
<style>
  .q2info-layout {
    width: min(100%, 980px);
    max-width: 980px;
    min-width: 0;
    margin: 0 auto;
    padding: 0 12px;
    box-sizing: border-box;
    font-family: sans-serif;
    color: #000;
  }

  .q2info-layout #q2infoToggleWrap,
  .q2info-layout #q2infoSelectWrap {
    text-align: center;
    margin: 8px 0;
  }

  .q2info-layout #q2InfoChartLimit,
  .q2info-layout #q2infoNorderToggle {
    font-size: 1em;
    margin-left: 6px;
  }

  .q2info-layout label[for="q2infoNorderToggle"] { margin-left: 8px; }

  .q2info-layout #q2infoStatsTable {
    margin: 10px auto;
    border-collapse: collapse;
    width: 60%;
    max-width: 100%;
  }

  .q2info-layout #q2infoStatsTable th,
  .q2info-layout #q2infoStatsTable td {
    border: 1px solid #999;
    padding: 6px 10px;
    text-align: center;
  }

  .q2info-layout .circle {
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

  .q2info-layout #infoBlock.info-list {
    display: flex;
    flex-direction: column;
    padding: 14px 16px;
    gap: 8px;
    font-size: 0.95em;
    width: 100%;
    max-width: none;
    margin: 30px 0;
    background: rgba(255,255,255,0.03);
    color: #333;
    box-sizing: border-box;
  }

  .q2info-layout .info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid #FF8C00;
    padding-left: 10px;
    background: rgba(255, 255, 255, 0.26);
    border-radius: 6px;
  }

  .q2info-layout .info-text { font-size: 0.95em; }

  .q2info-layout .table-wrapper {
    width: 100%;
    max-height: 70vh;
    overflow: auto;
    margin: 0 auto 12px;
    border: 1px solid #ccc;
    background: rgba(173, 216, 230, 0.85);
  }

  .q2info-layout table.digit-grid {
    width: max-content;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
  }

  .q2info-layout .digit-grid td,
  .q2info-layout .digit-grid th {
    width: 20px;
    height: 20px;
    text-align: center;
    border: 1px solid #ccc;
    padding: 0;
    box-sizing: border-box;
  }

  .q2info-layout .digit-grid th {
    height: 60px;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 0.7em;
    background: #eee;
  }

  .q2info-layout .digit-grid td.hit { background-color: #7eb0ea; }
  .q2info-layout .digit-grid td.repeat-2 { background-color: #f8c471; }

  .q2info-layout .digit-grid td:first-child,
  .q2info-layout .digit-grid th:first-child {
    background-color: #eee;
    position: sticky;
    left: 0;
    z-index: 1;
    font-weight: bold;
    color: #1f4fd8;
  }

  .q2info-layout .digit-grid td:nth-child(2),
  .q2info-layout .digit-grid th:nth-child(2) {
    background-color: #eee;
    font-weight: bold;
  }

  .q2info-layout .filter-form {
    text-align: center;
    margin: 0;
    padding: 10px 0;
    width: 100%;
  }

  .q2info-layout .filter-form select {
    font-size: 1em;
    border-radius: 6px;
    padding: 2px 6px;
    line-height: 1.2;
    box-sizing: border-box;
    min-height: 32px;
  }

  .q2info-layout .q2info-chart-wrap {
    max-width: 100%;
    margin: 12px auto;
    position: relative;
    height: min(50vh, 320px);
  }
</style>

<div class="q2info-layout">
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
              <td>&nbsp;<?= $digitSums[$digit] ?>x&nbsp;</td>
              <td><?= $digit ?></td>
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
      <p style="text-align:center; color: red;"><?= t('q2info.no_data') ?></p>
    <?php endif; ?>
  </div>

  <div class="filter-form">
    <?= t('q2info.latest') ?>
    <select id="q2InfoGridLimit">
      <?php foreach ([50, 100, 365] as $opt): ?>
        <option value="<?= $opt ?>" <?= ($gridLimit == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    <?= t('q2info.draws_suffix') ?>
  </div>

  <div class="q2info-chart-wrap">
    <canvas id="q2infoChart"></canvas>
  </div>

  <div id="q2infoToggleWrap">
    <input type="checkbox" id="q2infoNorderToggle" <?= $isNorder ? 'checked' : '' ?>>
    <label for="q2infoNorderToggle"><?= t('q2info.any_order') ?></label>
  </div>

  <div id="q2infoSelectWrap">
    <label for="q2InfoChartLimit"><?= t('q2info.last_draw_count') ?></label>
    <select id="q2InfoChartLimit">
      <option value="100" <?= ($limit == 100 ? 'selected' : '') ?>>100</option>
      <option value="200" <?= ($limit == 200 ? 'selected' : '') ?>>200</option>
      <option value="500" <?= ($limit == 500 ? 'selected' : '') ?>>500</option>
      <option value="1000" <?= ($limit == 1000 ? 'selected' : '') ?>>1000</option>
      <option value="0" <?= ($limit == 0 ? 'selected' : '') ?>><?= t('q2info.all') ?></option>
    </select>
  </div>

  <table id="q2infoStatsTable">
    <thead>
      <tr>
        <th><?= t('q2info.stats.days') ?></th>
        <th><?= t('q2info.stats.combo_count') ?></th>
        <th>%</th>
      </tr>
    </thead>
    <tbody id="q2infoStatsBody"></tbody>
  </table>

  <div id="infoBlock" class="info-list">
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">1</span>
        <span class="circle">2</span>
      </div>
      <div class="info-text"><?= t('q2info.info.all_combinations') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">2</span>
        <span class="circle">1</span>
      </div>
      <div class="info-text"><?= t('q2info.info.any_order_no_duplicates') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">0</span>
        <span class="circle">0</span>
      </div>
      <div class="info-text"><?= t('q2info.info.duplicates') ?></div>
    </div>
  </div>

  <div id="q2info-bootstrap" hidden data-json="<?= htmlspecialchars($bootJson, ENT_QUOTES, 'UTF-8') ?>"></div>

  <div id="scrollHint">⬇⬆</div>
</div>
