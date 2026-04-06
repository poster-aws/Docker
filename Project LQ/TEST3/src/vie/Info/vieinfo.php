<?php
require_once __DIR__ . '/../db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$tiragesGrid = [];
$numberSums = array_fill(1, 49, 0);
$allowedGridLimits = [50, 100, 200, 500];
$gridLimit = isset($_GET['grid_limit']) && in_array((int)$_GET['grid_limit'], $allowedGridLimits, true)
    ? (int)$_GET['grid_limit']
    : 50;

$vieCount = 0;
$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $vieConn->query('SELECT COUNT(*) AS total FROM Vie');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $vieCount = (int)$row['total'];
    }
    $sqlGrid = 'SELECT Tirage, n1, n2, n3, n4, n5, GN FROM Vie ORDER BY Tirage DESC LIMIT ' . (int)$gridLimit;
    $resGrid = $vieConn->query($sqlGrid);
    if ($resGrid && $resGrid->num_rows > 0) {
        while ($r = $resGrid->fetch_assoc()) {
            $nums = [(int)$r['n1'], (int)$r['n2'], (int)$r['n3'], (int)$r['n4'], (int)$r['n5']];
            $tiragesGrid[] = [
                'Tirage' => $r['Tirage'],
                'nums'   => $nums,
                'GN'     => (int)$r['GN'],
            ];
            foreach ($nums as $num) {
                if ($num >= 1 && $num <= 49) {
                    $numberSums[$num]++;
                }
            }
        }
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
    <p class="no-data">Aucun tirage.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tiragesGrid)): ?>
  <div class="filter-form">
    Dernières
    <select id="vieInfoGridLimit" name="grid_limit" aria-label="Nombre de tirages">
      <?php foreach ($allowedGridLimits as $opt): ?>
        <option value="<?= (int)$opt ?>" <?= ($gridLimit === $opt ? 'selected' : '') ?>><?= (int)$opt ?></option>
      <?php endforeach; ?>
    </select>
    tirages
  </div>
  <?php endif; ?>
</div>
