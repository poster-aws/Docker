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
    'texts' => [
        'chartCombinations' => t('q2info.chart.combinations'),
        'statsDays' => t('q2info.stats.days'),
        'statsComboCount' => t('q2info.stats.combo_count'),
        'tooltipPattern' => t('q2info.chart.tooltip'),
    ],
    'initialData' => $data,
];
$bootJson = json_encode($q2infoBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>
<div id="q2-meta" data-count="<?= (int)$q2count ?>" data-norder="<?= $isNorder ? '1' : '0' ?>"></div>
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

  <div class="filter-form info-filter-form">
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

  <div id="q2infoSelectWrap" class="info-filter-form--chart">
    <label for="q2InfoChartLimit"><?= t('q2info.last_draw_count') ?></label>
    <select id="q2InfoChartLimit" class="info-page-select">
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
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
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
</div>
