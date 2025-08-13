<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require_once "db.php"; // при необходимости общий конфиг

$toutConn = new mysqli("db", "user", "user", "toutourien");
$toutConn->set_charset("utf8");
if ($toutConn->connect_error) die("Connection failed: " . $toutConn->connect_error);

// Общее количество тиражей
$totalRes = $toutConn->query("SELECT COUNT(*) AS total FROM Tout");
$totalCount = ($totalRes && $row = $totalRes->fetch_assoc()) ? (int)$row['total'] : 0;

// Лимит — жёстко 50
$limit = 50;

// Последние 50 тиражей
$sql = "SELECT Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12
        FROM Tout
        ORDER BY Tirage DESC
        LIMIT $limit";
$res = $toutConn->query($sql);

$tirages = [];
if ($res && $res->num_rows > 0) {
  while ($r = $res->fetch_assoc()) {
    $nums = array_map('intval', [
      $r['n1'],$r['n2'],$r['n3'],$r['n4'],$r['n5'],$r['n6'],
      $r['n7'],$r['n8'],$r['n9'],$r['n10'],$r['n11'],$r['n12']
    ]);
    $tirages[] = [
      'Tirage' => $r['Tirage'],
      'nums'   => $nums,
      // ⚡ быстрый lookup: isset($flip[$digit])
      'flip'   => array_flip($nums),
    ];
  }
}
$toutConn->close();

// Подсчёт Tot по числам 1..24
$totals = array_fill(1, 24, 0);
foreach ($tirages as $t) {
  foreach ($t['nums'] as $num) {
    if ($num >= 1 && $num <= 24) $totals[$num]++;
  }
}
?>

<div id="tout-meta" data-count="<?= htmlspecialchars((string)$totalCount, ENT_QUOTES) ?>"></div>

<div class="table-wrapper tout-grid-wrapper">
  <?php if (!empty($tirages)): ?>
    <table class="digit-grid">
      <thead>
        <tr>
          <th class="sticky-left">Tot #</th>
          <?php foreach ($tirages as $t): ?>
            <th><?= htmlspecialchars($t['Tirage'], ENT_QUOTES) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
          $maxTot = max($totals);
          $thresholdHigh = $maxTot * 0.66;
          $thresholdMedium = $maxTot * 0.33;
        ?>
        <?php for ($digit = 1; $digit <= 24; $digit++): ?>
          <?php
            $val = $totals[$digit];
            $classTot = ($val >= $thresholdHigh) ? 'high' : (($val >= $thresholdMedium) ? 'medium' : 'low');
          ?>
          <tr>
            <td class="sticky-left sticky-label">
              <span class="tot <?= $classTot ?>">+<?= $val ?></span>
              <span class="digit-num"><?= $digit ?></span>
            </td>
            <?php foreach ($tirages as $t):
              $isHit = isset($t['flip'][$digit]); // быстрее, чем array_count_values + проверка
            ?>
              <td class="<?= $isHit ? 'hit' : '' ?>"><?= $isHit ? $digit : '' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="no-data">Нет данных для отображения.</p>
  <?php endif; ?>
</div>