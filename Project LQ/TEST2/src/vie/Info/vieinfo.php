<?php
require_once "../db.php";

$tiragesGrid = [];
$numberSums = array_fill(1, 49, 0);
$allowedGridLimits = [50, 100, 200, 500];
$gridLimit = isset($_GET['grid_limit']) && in_array((int)$_GET['grid_limit'], $allowedGridLimits)
    ? (int)$_GET['grid_limit']
    : 50;

$tableExists = $vieConn->query("SHOW TABLES LIKE 'Vie'");
if ($tableExists && $tableExists->num_rows > 0) {
    $sqlGrid = "SELECT Tirage, n1, n2, n3, n4, n5, GN FROM Vie ORDER BY Tirage DESC LIMIT " . (int)$gridLimit;
    $resGrid = $vieConn->query($sqlGrid);
    if ($resGrid && $resGrid->num_rows > 0) {
        while ($r = $resGrid->fetch_assoc()) {
            $nums = [(int)$r['n1'], (int)$r['n2'], (int)$r['n3'], (int)$r['n4'], (int)$r['n5']];
            $tiragesGrid[] = [
                'Tirage' => $r['Tirage'],
                'nums'   => $nums,
                'GN'     => (int)$r['GN']
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
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Info Grande Vie</title>
  <link rel="stylesheet" href="../vie.css">
</head>
<body>

<div class="vie-info-content">
  <h2>Grande Vie — grille</h2>
  <p>n1…n5 : 1–49, sans répétition. GN : Grand Numéro.</p>

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
        <?php for ($num = 1; $num <= 49; $num++): ?>
          <tr>
            <td>&nbsp;<?= $numberSums[$num] ?>×&nbsp;</td>
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
    <p>Aucun tirage.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($tiragesGrid)): ?>
  <form class="filter-form" method="get">
    Dernières
    <select name="grid_limit" onchange="this.form.submit()">
      <?php foreach ($allowedGridLimits as $opt): ?>
        <option value="<?= $opt ?>" <?= ($gridLimit === $opt ? 'selected' : '') ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    tirages
  </form>
  <?php endif; ?>
</div>

</body>
</html>
