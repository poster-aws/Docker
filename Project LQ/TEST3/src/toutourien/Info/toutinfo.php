<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$totalRes = $toutConn->query('SELECT COUNT(*) AS total FROM Tout');
$totalCount = ($totalRes && $row = $totalRes->fetch_assoc()) ? (int)$row['total'] : 0;

$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits, true)
    ? (int)$_GET['limit']
    : 50;

$sql = "SELECT Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout ORDER BY Tirage DESC LIMIT $limit";
$res = $toutConn->query($sql);
$tirages = [];
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $tirages[] = [
            'Tirage' => $r['Tirage'],
            'nums' => array_map('intval', [
                $r['n1'], $r['n2'], $r['n3'], $r['n4'],
                $r['n5'], $r['n6'], $r['n7'], $r['n8'],
                $r['n9'], $r['n10'], $r['n11'], $r['n12'],
            ]),
        ];
    }
}

$positionMatrix = [];
for ($i = 1; $i <= 12; $i++) {
    $posKey = 'n' . $i;
    $positionMatrix[$posKey] = array_fill(1, 24, 0);
}

foreach ($tirages as $t) {
    foreach ($t['nums'] as $i => $val) {
        $posKey = 'n' . ($i + 1);
        if ($val >= 1 && $val <= 24) {
            $positionMatrix[$posKey][$val]++;
        }
    }
}

$sqlAll = 'SELECT n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout';
$resAll = $toutConn->query($sqlAll);

$comboSet = [];
$comboCounts = [];
$totalPossibleComb = 2704156;

if ($resAll && $resAll->num_rows > 0) {
    while ($r = $resAll->fetch_assoc()) {
        $nums = array_map('intval', [
            $r['n1'], $r['n2'], $r['n3'], $r['n4'], $r['n5'], $r['n6'],
            $r['n7'], $r['n8'], $r['n9'], $r['n10'], $r['n11'], $r['n12'],
        ]);
        sort($nums);
        $key = implode(',', $nums);
        $comboSet[$key] = true;
        if (!isset($comboCounts[$key])) {
            $comboCounts[$key] = 0;
        }
        $comboCounts[$key]++;
    }
}

$totalActualComb = count($comboSet);
$repeatedCombos = array_filter($comboCounts, function ($cnt) {
    return $cnt > 1;
});
arsort($repeatedCombos);

$toutConn->close();

function toutinfo_format_int(int $n, string $lang): string
{
    if ($lang === 'en') {
        return number_format($n, 0, '.', ',');
    }
    return number_format($n, 0, ',', ' ');
}
?>
<div id="tout-meta" data-count="<?= $totalCount ?>"></div>
<div class="toutinfo-layout">

<?php if (!empty($positionMatrix)): ?>
<div class="table-wrapper toutinfo-analyse-wrap">
  <table class="analyse-grid">
    <thead>
      <tr>
        <th></th>
        <?php for ($n = 1; $n <= 24; $n++): ?>
          <th><?= $n ?></th>
        <?php endfor; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($positionMatrix as $pos => $counts): ?>
        <tr>
          <td class="sticky-label"><?= htmlspecialchars(strtoupper($pos), ENT_QUOTES, 'UTF-8') ?></td>
          <?php foreach ($counts as $val): ?>
            <?php if ($val === 0): ?>
              <td class="empty"></td>
            <?php else: ?>
              <td><?= (int)$val ?></td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
  <p class="toutinfo-no-data"><?= t('toutinfo.no_data') ?></p>
<?php endif; ?>

<div class="filter-form toutinfo-filter">
  <?= t('toutinfo.latest') ?>
  <select id="toutInfoLimit" name="limit">
    <?php foreach ($allowedLimits as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select>
  <?= t('toutinfo.draws_suffix') ?>
</div>

<div id="infoBlock" class="info-list">
  <div class="info-row info-row--schedule">
    <span class="info-sign" aria-hidden="true">&#8505;</span>
    <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <div class="info-row">
    <div class="info-text">
      <?= t('toutinfo.info.all_possible') ?> — <b><?= toutinfo_format_int($totalPossibleComb, $lang) ?></b>
    </div>
  </div>
  <div class="info-row">
    <div class="info-text">
      <?= t('toutinfo.info.drawn_unique') ?> — <b><?= toutinfo_format_int($totalActualComb, $lang) ?></b>
    </div>
  </div>

  <?php if (!empty($repeatedCombos)): ?>
    <div class="info-row">
      <div class="info-text"><?= t('toutinfo.info.repeated_intro') ?></div>
    </div>
    <?php foreach ($repeatedCombos as $combo => $cnt): ?>
      <div class="info-row">
        <div class="info-text">
          <?php foreach (explode(',', $combo) as $d): ?>
            <span class="combo-square"><?= htmlspecialchars(trim($d), ENT_QUOTES, 'UTF-8') ?></span>
          <?php endforeach; ?>
          — <b><?= (int)$cnt ?></b>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</div>
