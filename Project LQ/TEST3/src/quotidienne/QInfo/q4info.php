<?php
require_once "../db.php";
require_once __DIR__ . "/../../i18n.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$countResult = $conn->query("SELECT COUNT(*) as total FROM Q4");
$q4count = 0;
if ($countResult && $row = $countResult->fetch_assoc()) {
    $q4count = (int)$row['total'];
}

function isUniqueCombo($n1, $n2, $n3, $n4) {
    return count(array_unique([$n1, $n2, $n3, $n4])) === 4;
}

function getComboType($nums) {
    $count = array_count_values($nums);
    $values = array_values($count);
    rsort($values);

    if (count($count) === 4) return 'unique';
    if (count($count) === 3) return 'onepair';
    if (count($count) === 2 && in_array(2, $values) && $values[0] === 2) return 'twopairs';
    if ($values[0] === 3) return 'triplet';
    return 'other';
}

$fois1      = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 1");

$freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0");

// Fetch "all digits the same" with Fois = 0
$allSameZero = $conn->query("
  SELECT n1
  FROM Q4_fois
  WHERE Fois = 0 AND n1 = n2 AND n2 = n3 AND n3 = n4
  GROUP BY n1
  ORDER BY n1
");

/* === Вариант А: группы среди fois=0, где есть минимум 2 перестановки одной мультикомбинации ===
   rep_key  — любой представитель группы (минимальная строка n1n2n3n4);
   cnt      — число перестановок в группе среди fois=0;
   members  — список всех перестановок (как строки n1n2n3n4) для выпадающего окна.
*/

$dupsA = $conn->query("
  SELECT
    MIN(CONCAT(n1,n2,n3,n4)) AS rep_key,
    COUNT(*)                 AS cnt,
    GROUP_CONCAT(CONCAT(n1,n2,n3,n4) ORDER BY n1,n2,n3,n4 SEPARATOR ',') AS members
  FROM Q4_fois
  WHERE Fois = 0
  GROUP BY CONCAT_WS('|',
    (n1=0)+(n2=0)+(n3=0)+(n4=0),
    (n1=1)+(n2=1)+(n3=1)+(n4=1),
    (n1=2)+(n2=2)+(n3=2)+(n4=2),
    (n1=3)+(n2=3)+(n3=3)+(n4=3),
    (n1=4)+(n2=4)+(n3=4)+(n4=4),
    (n1=5)+(n2=5)+(n3=5)+(n4=5),
    (n1=6)+(n2=6)+(n3=6)+(n4=6),
    (n1=7)+(n2=7)+(n3=7)+(n4=7),
    (n1=8)+(n2=8)+(n3=8)+(n4=8),
    (n1=9)+(n2=9)+(n3=9)+(n4=9)
  )
  HAVING COUNT(*) >= 2
  ORDER BY cnt DESC
");

function key_to_digits($key4) {
    return [
        (int)substr($key4, 0, 1),
        (int)substr($key4, 1, 1),
        (int)substr($key4, 2, 1),
        (int)substr($key4, 3, 1)
    ];
}

/* === GRID Q4 (как Q2 / Q3) === */
$allowedGridLimits = [50, 100, 200, 500];
$gridLimit = isset($_GET['grid_limit']) && in_array((int)$_GET['grid_limit'], $allowedGridLimits)
    ? (int)$_GET['grid_limit']
    : 50;

$sqlGrid = "SELECT Tirage, n1, n2, n3, n4 FROM Q4 ORDER BY Tirage DESC LIMIT $gridLimit";
$resGrid = $conn->query($sqlGrid);

$tiragesGrid = [];
if ($resGrid && $resGrid->num_rows > 0) {
    while ($r = $resGrid->fetch_assoc()) {
        $tiragesGrid[] = [
            'Tirage' => $r['Tirage'],
            'nums'   => [(int)$r['n1'], (int)$r['n2'], (int)$r['n3'], (int)$r['n4']]
        ];
    }
}

/* сумма выпадений цифр 0–9 (учитывая дубли) */
$digitSums = array_fill(0, 10, 0);
foreach ($tiragesGrid as $t) {
    foreach ($t['nums'] as $num) {
        $digitSums[$num]++;
    }
}
/* === /GRID Q4 === */
?>
<div id="q4-meta" data-count="<?= $q4count ?>"></div>
<div class="q4info-layout">

<!-- === GRID Q4 (как Q2 / Q3) === -->
<div class="table-wrapper">
<?php if (!empty($tiragesGrid)): ?>
<table class="digit-grid">
  <thead>
    <tr>
      <th>Σ</th>
      <th>#</th>
      <?php foreach ($tiragesGrid as $t): ?>
        <th><?= htmlspecialchars($t['Tirage']) ?></th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php for ($digit = 0; $digit <= 9; $digit++): ?>
      <tr>
        <td>&nbsp;<?= $digitSums[$digit] ?>x&nbsp;</td> <!-- --тут Х -->
        <!-- # -->
        <td><?= $digit ?></td>
        <!-- столбцы тиражей -->
        <?php foreach ($tiragesGrid as $t):
          $cnt = array_count_values($t['nums'])[$digit] ?? 0;
          if ($cnt === 4)      $class = 'repeat-4';
          elseif ($cnt === 3)  $class = 'repeat-3';
          elseif ($cnt === 2)  $class = 'repeat-2';
          elseif ($cnt === 1)  $class = 'hit';
          else                 $class = '';
        ?>
          <td class="<?= $class ?>"><?= $cnt ? $digit : '' ?></td>
        <?php endforeach; ?>
      </tr>
    <?php endfor; ?>
  </tbody>
</table>
<?php endif; ?>
</div>

<div class="filter-form">
  <?= t('q4info.latest') ?>
  <select id="q4InfoLimit">
    <?php foreach ([50,100,200,500] as $opt): ?>
      <option value="<?= $opt ?>" <?= ($gridLimit==$opt?'selected':'') ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select>
  <?= t('q4info.draws_suffix') ?>
</div>
<!-- === /GRID Q4 === -->

<div class="q4info-tables">
  <!-- Fois = 1 -->
  <div class="table-container">
    <table class="interactive-table" id="statsOrderTable" data-base-title="<?= htmlspecialchars(t('q4info.header.once_drawn')) ?>" data-combs-label="<?= htmlspecialchars(t('q4info.header.combs')) ?>">
      <thead>
        <tr>
          <th colspan="4">
            <div class="q4info-thead-stack">
              <div class="q4info-head-band q4info-head-title"></div>
              <div class="q4info-head-filters">
                <select class="q4info-filter" data-table-id="statsOrderTable">
                  <option value="all"><?= t('q4info.filter.all_in_order') ?></option>
                  <option value="unique"><?= t('q4info.filter.unique') ?></option>
                  <option value="onepair"><?= t('q4info.filter.onepair') ?></option>
                  <option value="twopairs"><?= t('q4info.filter.twopairs') ?></option>
                  <option value="triplet"><?= t('q4info.filter.triplet') ?></option>
                </select>
              </div>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $fois1->fetch_assoc()):
          $comboType = getComboType([$row['n1'], $row['n2'], $row['n3'], $row['n4']]); ?>
          <tr data-combo-type="<?= $comboType ?>" class="<?= isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '' ?>">
            <td>
              <span class="circle"><?= $row['n1'] ?></span>
              <span class="circle"><?= $row['n2'] ?></span>
              <span class="circle"><?= $row['n3'] ?></span>
              <span class="circle"><?= $row['n4'] ?></span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Fois = 0 -->
  <div class="combo-table-container">
    <table class="interactive-table" id="freeOrderTable" data-base-title="<?= htmlspecialchars(t('q4info.header.never_drawn')) ?>" data-combs-label="<?= htmlspecialchars(t('q4info.header.combs')) ?>">
      <thead>
        <tr>
          <th colspan="4">
            <div class="q4info-thead-stack">
              <div class="q4info-head-band q4info-head-title"></div>
              <div class="q4info-head-filters">
                <select class="q4info-filter" data-table-id="freeOrderTable">
                  <option value="all"><?= t('q4info.filter.all_in_order') ?></option>
                  <option value="unique"><?= t('q4info.filter.unique') ?></option>
                  <option value="onepair"><?= t('q4info.filter.onepair') ?></option>
                  <option value="twopairs"><?= t('q4info.filter.twopairs') ?></option>
                  <option value="triplet"><?= t('q4info.filter.triplet') ?></option>
                </select>
              </div>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $freeOrder->fetch_assoc()):
          $comboType = getComboType([$row['n1'], $row['n2'], $row['n3'], $row['n4']]); ?>
          <tr data-combo-type="<?= $comboType ?>" class="<?= isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '' ?>">
            <td>
              <span class="circle"><?= $row['n1'] ?></span>
              <span class="circle"><?= $row['n2'] ?></span>
              <span class="circle"><?= $row['n3'] ?></span>
              <span class="circle"><?= $row['n4'] ?></span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- === Новая третья таблица: «Вариант А» вместо старой === -->
  <div class="number-stats-table">
    <table class="interactive-table" id="dupsVariantATable">
      <thead>
        <tr><th colspan="5"><?= t('q4info.never_drawn_any_order') ?></th></tr>
        <!-- <tr>
          <th>n1</th><th>n2</th><th>n3</th><th>n4</th><th>count</th>
        </tr> -->
      </thead>
      <tbody>
        <?php
        // Render "all digits the same" with Fois=0 at the top
        ?>
        <?php if (isset($allSameZero) && $allSameZero && $allSameZero->num_rows > 0): ?>
          <?php while ($s = $allSameZero->fetch_assoc()):
            $d = (int)$s['n1']; ?>
            <tr>
              <td>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
              </td>
              <td>
                <button class="count-btn" disabled>-</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        <?php
        $rowIndex = 0;
        while ($r = $dupsA->fetch_assoc()):
          $rowIndex++;
          [$a,$b,$c,$d] = key_to_digits($r['rep_key']);
          $cnt = (int)$r['cnt'];
          $members = array_filter(explode(',', $r['members']), fn($s) => $s !== '');
        ?>
        <tr>
          <td>
            <span class="circle"><?= $a ?></span>
            <span class="circle"><?= $b ?></span>
            <span class="circle"><?= $c ?></span>
            <span class="circle"><?= $d ?></span>
          </td>
          <td>
            <div class="dropdown">
              <button type="button" class="count-btn q4info-members-btn" data-members-target="m<?= $rowIndex ?>"><?= $cnt ?></button>
              <div class="dropdown-panel" id="m<?= $rowIndex ?>">
                <?php foreach ($members as $mk):
                  $da = (int)substr($mk,0,1);
                  $db = (int)substr($mk,1,1);
                  $dc = (int)substr($mk,2,1);
                  $dd = (int)substr($mk,3,1);
                ?>
                  <div class="member-item">
                    <span class="circle"><?= $da ?></span>
                    <span class="circle"><?= $db ?></span>
                    <span class="circle"><?= $dc ?></span>
                    <span class="circle"><?= $dd ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Информационный блок (без изменений) -->
<div id="infoBlock" class="info-list">
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">9</span><span class="circle">9</span><span class="circle">9</span><span class="circle">9</span>
    </div>
    <div class="info-text"><?= t('q4info.info.all_combinations') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">3</span><span class="circle">4</span>
    </div>
    <div class="info-text"><?= t('q4info.info.all_different') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">1</span><span class="circle">2</span><span class="circle">3</span>
    </div>
    <div class="info-text"><?= t('q4info.info.one_pair') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">2</span>
    </div>
    <div class="info-text"><?= t('q4info.info.two_pairs') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">1</span><span class="circle">1</span><span class="circle">8</span>
    </div>
    <div class="info-text"><?= t('q4info.info.triplet') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">8</span><span class="circle">8</span><span class="circle">8</span><span class="circle">8</span>
    </div>
    <div class="info-text"><?= t('q4info.info.any_order_all_combinations') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">4</span><span class="circle">3</span><span class="circle">2</span><span class="circle">1</span>
    </div>
    <div class="info-text"><?= t('q4info.info.any_order_all_different') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">3</span><span class="circle">1</span>
    </div>
    <div class="info-text"><?= t('q4info.info.any_order_one_pair') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">2</span>
    </div>
    <div class="info-text"><?= t('q4info.info.any_order_two_pairs') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">1</span>
    </div>
    <div class="info-text"><?= t('q4info.info.any_order_triplet') ?></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">7</span><span class="circle">7</span><span class="circle">7</span><span class="circle">7</span>
    </div>
    <div class="info-text"><?= t('q4info.info.four_identical') ?></div>
  </div>
</div>
<!-- Информационный блок конец-->

</div>