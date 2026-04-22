<?php
require_once "../db.php";
require_once __DIR__ . "/../../i18n.php";

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

  <div class="filter-form info-filter-form">
    <?= t('q3info.latest') ?>
    <select id="q3InfoLimit">
      <?php foreach ([50, 100, 200, 500] as $opt): ?>
        <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    <?= t('q3info.draws_suffix') ?>
  </div>

  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="info-row info-row--schedule">
      <span class="info-sign info-sign--cost" aria-hidden="true">$</span>
      <div class="info-text"><?= t('infoblock.cost.q234') ?></div>
    </div>
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
