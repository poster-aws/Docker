<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$tiragesGrid = [];
$numberSums = array_fill(1, 49, 0);
$gnSums = array_fill(1, 7, 0);
/* Grille : N derniers tirages = N dates Tirage distinctes avec GN 1–7 (lignes GN = 0 exclues). */
$allowedGridLimits = [50, 100, 200];
$gridLimit = isset($_GET['grid_limit']) && in_array((int) $_GET['grid_limit'], $allowedGridLimits, true)
    ? (int) $_GET['grid_limit']
    : 50;

$vieCount = 0;
$vieCombOut = 0;
$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $vieConn->query('SELECT COUNT(*) AS total FROM Vie');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $vieCount = (int)$row['total'];
    }
    $n = (int) $gridLimit;
    $sqlGrid = "SELECT v.Tirage, v.n1, v.n2, v.n3, v.n4, v.n5, v.GN FROM Vie v
        INNER JOIN (
            SELECT Tirage FROM Vie WHERE GN >= 1 AND GN <= 7
            GROUP BY Tirage ORDER BY Tirage DESC LIMIT {$n}
        ) AS recent ON v.Tirage = recent.Tirage AND v.GN >= 1 AND v.GN <= 7
        ORDER BY v.Tirage DESC, v.GN ASC";
    $resGrid = $vieConn->query($sqlGrid);
    if ($resGrid && $resGrid->num_rows > 0) {
        $seenTirage = [];
        while ($r = $resGrid->fetch_assoc()) {
            $key = (string) $r['Tirage'];
            if (isset($seenTirage[$key])) {
                continue;
            }
            $seenTirage[$key] = true;
            $nums = [(int) $r['n1'], (int) $r['n2'], (int) $r['n3'], (int) $r['n4'], (int) $r['n5']];
            $gnVal = (int) $r['GN'];
            $tiragesGrid[] = [
                'Tirage' => $r['Tirage'],
                'nums'   => $nums,
                'GN'     => $gnVal,
            ];
            if ($gnVal >= 1 && $gnVal <= 7) {
                $gnSums[$gnVal]++;
            }
            foreach ($nums as $num) {
                if ($num >= 1 && $num <= 49) {
                    $numberSums[$num]++;
                }
            }
        }
    }
}

$chkVieInfo = $vieConn->query("SHOW TABLES LIKE 'Vie_info'");
if ($chkVieInfo && $chkVieInfo->num_rows > 0) {
    $infoRes = $vieConn->query('SELECT Comb_out FROM Vie_info LIMIT 1');
    if ($infoRes && $infoRow = $infoRes->fetch_assoc()) {
        $vieCombOut = (int) $infoRow['Comb_out'];
    }
}

$vieConn->close();
?>
<div id="vie-meta" data-count="<?= (int)$vieCount ?>"></div>

<div class="vie-layout vie-layout--info">
  <div class="table-wrapper vie-grid-wrapper" data-limit="<?= (int)$gridLimit ?>">
    <?php if (!empty($tiragesGrid)): ?>
    <table class="digit-grid" id="vieGrid">
      <thead>
        <tr>
          <th>Σ</th>
          <th>#</th>
          <?php foreach ($tiragesGrid as $t): ?>
            <th><?= htmlspecialchars((string)$t['Tirage'], ENT_QUOTES, 'UTF-8') ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($num = 1; $num <= 49; $num++): ?>
          <tr>
            <td>&nbsp;<?= (int)$numberSums[$num] ?>x&nbsp;</td>
            <td><?= $num ?></td>
            <?php foreach ($tiragesGrid as $t):
              $cnt = array_count_values($t['nums'])[$num] ?? 0;
              $class = ($cnt === 1) ? 'hit' : '';
            ?>
              <td class="<?= $class ?>"><?= $cnt ? $num : '' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endfor; ?>
        <tr class="row-gn">
          <td colspan="2"><strong>GN</strong></td>
          <?php foreach ($tiragesGrid as $t): ?>
            <td class="cell-gn"><?= (int)$t['GN'] ?></td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
    <?php else: ?>
    <p class="no-data"><?= htmlspecialchars(t('vieinfo.no_data'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tiragesGrid)): ?>
  <div class="table-wrapper vie-grid-wrapper vie-grid-wrapper--gn" data-limit="<?= (int)$gridLimit ?>">
    <table class="digit-grid" id="vieGnInfoGrid">
      <thead>
        <tr>
          <th>Σ</th>
          <th>#</th>
          <?php foreach ($tiragesGrid as $t): ?>
            <th><?= htmlspecialchars((string) $t['Tirage'], ENT_QUOTES, 'UTF-8') ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($g = 1; $g <= 7; $g++): ?>
          <tr>
            <td>&nbsp;<?= (int) $gnSums[$g] ?>x&nbsp;</td>
            <td><?= $g ?></td>
            <?php foreach ($tiragesGrid as $t):
              $gv = (int) ($t['GN'] ?? 0);
              $hit = ($gv === $g);
              ?>
              <td class="<?= $hit ? 'hit' : '' ?>"><?= $hit ? $g : '' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>

  <div class="filter-form info-filter-form vie-info-grids-filter">
    <?= htmlspecialchars(t('vieinfo.filter.prefix'), ENT_QUOTES, 'UTF-8') ?>
    <select id="vieInfoGridLimit" name="grid_limit" title="<?= htmlspecialchars(t('vieinfo.select_title'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(t('vieinfo.select_title'), ENT_QUOTES, 'UTF-8') ?>">
      <?php foreach ($allowedGridLimits as $opt): ?>
        <option value="<?= (int)$opt ?>" <?= ($gridLimit === $opt ? 'selected' : '') ?>><?= (int)$opt ?></option>
      <?php endforeach; ?>
    </select>
    <?= htmlspecialchars(t('vieinfo.filter.suffix'), ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.vie_biweekly'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="info-row info-row--schedule">
      <span class="info-sign info-sign--cost" aria-hidden="true">$</span>
      <div class="info-text"><?= t('vieinfo.info.cost') ?></div>
    </div>
    <div class="info-row info-row--schedule">
      <span class="info-sign info-sign--sum" aria-hidden="true">&#8721;</span>
      <div class="info-text"><?= sprintf(t('vieinfo.info.unique_combos'), (int) $vieCombOut) ?></div>
    </div>
    <div class="info-row">
      <div class="info-text"><?= t('vieinfo.info.all_combinations') ?></div>
    </div>
  </div>
</div>
