<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Подключение к БД ===
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
    <table class="digit-grid" id="toutGrid">
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
              $isHit = isset($t['flip'][$digit]);
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

<!-- Якорь для «закрыть» -->
<span id="tout-root"></span>

<!-- ===== КНОПКА ПОД ТАБЛИЦЕЙ ===== -->
<div style="max-width:98%; margin:10px auto 20px; text-align:center;">
  <a href="#verifierModal" style="
    display:inline-block; padding:8px 14px; border:1px solid #bdbdbd; border-radius:10px;
    background:#e6f0ff; cursor:pointer; font-size:14px; text-decoration:none; color:#000;">
    Проверить комбинацию
  </a>
</div>

<!-- ===== МОДАЛКА БЕЗ JS (через :target) ===== -->
<style>
  /* По умолчанию скрыто */
  #verifierModal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    align-items: center; justify-content: center;
    padding: 20px; z-index: 9999;
  }
  /* Показать, когда #verifierModal в адресной строке (…#verifierModal) */
  #verifierModal:target { display: flex; }

  #verifierModal .modal-dialog {
  background: rgba(255, 255, 255, 0.46); /* полупрозрачный белый */
  border-radius: 12px;
  width: min(490px, 48vw);   /* в 2 раза меньше */
  height: min(350px, 46vh);  /* в 2 раза меньше */
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,.35);
}

  #verifierModal .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-bottom: 1px solid #eee; background: #f8f9fb;
  }
  #verifierModal .modal-header h3 { margin: 0; font-size: 16px; font-weight: 600; }
  #verifierModal .modal-close {
    text-decoration: none; border: none; background: transparent;
    font-size: 22px; line-height: 1; cursor: pointer; color: #000;
  }
  #verifierModal .modal-body { flex: 1; overflow: hidden; }
  #verifierModal .modal-body iframe {
    width: 100%; 
    height: 100%; 
    border: none; 
    background: transparent !important; /* прозрачный фон iframe */
    display: block;
  }
</style>

<div id="verifierModal" role="dialog" aria-modal="true" aria-labelledby="verifierTitle">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 id="verifierTitle">Проверка комбинации</h3>
      <!-- Закрыть: возвращаемся к #tout-root (любой элемент-якорь в этом фрагменте) -->
      <a href="#tout-root" class="modal-close" aria-label="Закрыть">×</a>
    </div>
    <div class="modal-body">
      <!-- Код грузится напрямую из verifier.php -->
      <iframe src="../outis/verifier.php" loading="lazy" referrerpolicy="no-referrer"></iframe>
    </div>
  </div>
</div>