<?php
require_once "db.php";
require_once __DIR__ . "/../i18n.php";

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$countResult = $conn->query("SELECT COUNT(*) as total FROM Q3");
$q3count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q3count = (int)$row['total'];
}

$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits, true) ? (int)$_GET['limit'] : 50;

$sqlGrid = "SELECT Tirage, n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT $limit";
$resGrid = $conn->query($sqlGrid);
$tirages = [];
if ($resGrid && $resGrid->num_rows > 0) {
    while ($r = $resGrid->fetch_assoc()) {
        $tirages[] = [
            'Tirage' => $r['Tirage'],
            'nums' => [$r['n1'], $r['n2'], $r['n3']]
        ];
    }
}

$digitSums = array_fill(0, 10, 0);
foreach ($tirages as $t) {
    foreach ($t['nums'] as $num) {
        $digitSums[$num]++;
    }
}

$conn->close();
?>
<div id="q3-meta" data-count="<?= $q3count ?>"></div>
<style>
  .q3info-layout {
    width: min(100%, 980px);
    max-width: 980px;
    min-width: 0;
    margin: 0 auto;
    padding: 0 12px;
    box-sizing: border-box;
    font-family: sans-serif;
  }

  .q3info-layout .table-wrapper {
    width: 100%;
    max-height: 70vh;
    overflow: auto;
    margin: 0 0 12px;
    border: 1px solid #ccc;
    background: rgba(173, 216, 230, 0.85);
  }

  .q3info-layout table.digit-grid {
    width: max-content;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
    color: #000;
  }

  .q3info-layout .digit-grid td,
  .q3info-layout .digit-grid th {
    width: 20px;
    height: 20px;
    text-align: center;
    border: 1px solid #ccc;
    padding: 0;
    box-sizing: border-box;
  }

  .q3info-layout .digit-grid th {
    height: 60px;
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 0.7em;
    background: #eee;
    color: #000;
  }

  .q3info-layout .digit-grid td.hit { background-color: #7eb0ea; }
  .q3info-layout .digit-grid td.repeat-2 { background-color: #f8c471; }
  .q3info-layout .digit-grid td.repeat-3 { background-color: #e74c3c; }

  .q3info-layout .digit-grid td:first-child,
  .q3info-layout .digit-grid th:first-child {
    background-color: #eee;
    position: sticky;
    left: 0;
    z-index: 1;
    font-weight: bold;
    color: #1f4fd8;
  }

  .q3info-layout .digit-grid td:nth-child(2),
  .q3info-layout .digit-grid th:nth-child(2) {
    background-color: #eee;
    font-weight: bold;
  }

  .q3info-layout .filter-form {
    text-align: center;
    margin: 0;
    padding: 6px 0 14px;
    width: 100%;
  }

  .q3info-layout .filter-form select {
    font-size: 1em;
    border-radius: 6px;
    box-sizing: border-box;
    min-height: 32px;
    height: 32px;
    padding: 0 8px;
    line-height: 32px;
    vertical-align: middle;
  }

  .q3info-layout .circle {
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

  .q3info-layout #infoBlock.info-list {
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

  .q3info-layout .info-row {
    display: flex;
    align-items: center;
    gap: 12px;
    border-left: 4px solid #FF8C00;
    padding-left: 10px;
    background: rgba(255, 255, 255, 0.26);
    border-radius: 6px;
  }

  .q3info-layout .info-text { font-size: 0.95em; }
</style>
<div class="q3info-layout">
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
                if ($count === 3) {
                    $class = 'repeat-3';
                } elseif ($count === 2) {
                    $class = 'repeat-2';
                } elseif ($count === 1) {
                    $class = 'hit';
                } else {
                    $class = '';
                }
              ?>
                <td class="<?= $class ?>"><?= $count > 0 ? $digit : '' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; color:red;"><?= t('q3info.no_data') ?></p>
    <?php endif; ?>
  </div>

  <div class="filter-form">
    <?= t('q3info.latest') ?>
    <select id="q3InfoLimit">
      <?php foreach ([50, 100, 200, 500] as $opt): ?>
        <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    <?= t('q3info.draws_suffix') ?>
  </div>

  <div id="infoBlock" class="info-list">
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">1</span>
        <span class="circle">2</span>
        <span class="circle">3</span>
      </div>
      <div class="info-text"><?= t('q3info.info.all_combinations') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">4</span>
        <span class="circle">5</span>
        <span class="circle">6</span>
      </div>
      <div class="info-text"><?= t('q3info.info.all_different') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">1</span>
        <span class="circle">1</span>
        <span class="circle">2</span>
      </div>
      <div class="info-text"><?= t('q3info.info.one_pair') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">3</span>
        <span class="circle">1</span>
        <span class="circle">2</span>
      </div>
      <div class="info-text"><?= t('q3info.info.any_order_all_combinations') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">3</span>
        <span class="circle">1</span>
        <span class="circle">2</span>
      </div>
      <div class="info-text"><?= t('q3info.info.any_order_all_different') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">1</span>
        <span class="circle">2</span>
        <span class="circle">1</span>
      </div>
      <div class="info-text"><?= t('q3info.info.any_order_one_pair') ?></div>
    </div>
    <div class="info-row">
      <div class="info-digits">
        <span class="circle">7</span>
        <span class="circle">7</span>
        <span class="circle">7</span>
      </div>
      <div class="info-text"><?= t('q3info.info.three_identical') ?></div>
    </div>
  </div>
</div>
