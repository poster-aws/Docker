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

// === Расчёт: всего возможных и реально вышедших комбинаций ===
$totalPossibleComb = 2704156;

// === Повторное подключение для анализа всей таблицы ===
$toutConn = new mysqli("db", "user", "user", "toutourien");
$toutConn->set_charset("utf8");
if ($toutConn->connect_error) die("Connection failed: " . $toutConn->connect_error);

// Загружаем все комбинации
$sqlAll = "SELECT n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout";
$resAll = $toutConn->query($sqlAll);

$comboSet = [];
if ($resAll && $resAll->num_rows > 0) {
  while ($r = $resAll->fetch_assoc()) {
    $nums = array_map('intval', [
      $r['n1'],$r['n2'],$r['n3'],$r['n4'],$r['n5'],$r['n6'],
      $r['n7'],$r['n8'],$r['n9'],$r['n10'],$r['n11'],$r['n12']
    ]);
    sort($nums); // для уникальности независимо от порядка
    $key = implode(',', $nums);
    $comboSet[$key] = true;
  }
}
// Подсчёт реально вышедших уникальных комбинаций
$toutConn->close();
$totalActualComb = count($comboSet);

// === Повторяющиеся комбинации (встречающиеся более 1 раза)
$comboCounts = [];
$resAll->data_seek(0); // сброс курсора результата
while ($r = $resAll->fetch_assoc()) {
  $nums = array_map('intval', [
    $r['n1'],$r['n2'],$r['n3'],$r['n4'],$r['n5'],$r['n6'],
    $r['n7'],$r['n8'],$r['n9'],$r['n10'],$r['n11'],$r['n12']
  ]);
  sort($nums);
  $key = implode(',', $nums);
  if (!isset($comboCounts[$key])) $comboCounts[$key] = 0;
  $comboCounts[$key]++;
}
// Фильтруем только те, что встречаются более одного раза
$repeatedCombos = array_filter($comboCounts, fn($cnt) => $cnt > 1);
arsort($repeatedCombos); // сортировка по убыванию

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
<div style="max-width:98%; margin:10px auto; text-align:center;">
  <a href="#verifierModal" onclick="(function(){
    const d=document.querySelector('#verifierModal .modal-dialog');
    if(d){ d.style.setProperty('--tx','0px'); d.style.setProperty('--ty','0px'); }
    setTimeout(function(){ const m=document.getElementById('verifierModal'); if(m){ if(!m.hasAttribute('tabindex')) m.setAttribute('tabindex','-1'); m.focus({preventScroll:true}); }}, 0);
  })()" style="
    display:inline-block; padding:8px 14px; border:1px solid #bdbdbd; border-radius:10px;
    background:#e6f0ff; cursor:pointer; font-size:14px; text-decoration:none; color:#000;">
    Проверить комбинацию
  </a>
</div>

<!-- ===== ОБЪЕДИНЁННЫЙ ИНФОБЛОК ===== -->
<div class="info-list" style="max-width:800px; margin:10px auto;">
  <div class="info-row">
    <div class="info-text">
      Toutes les combinaisons possibles — <b><?= number_format($totalPossibleComb, 0, '.', ' ') ?></b>
    </div>
  </div>
  <div class="info-row">
    <div class="info-text">
      Combinaisons sorties — <b><?= number_format($totalActualComb, 0, '.', ' ') ?></b>
    </div>
  </div>

  <?php if (!empty($repeatedCombos)): ?>
    <div class="info-row">
      <div class="info-text"> Combinaisons sorties plusieurs fois </div>
    </div>
    <?php foreach ($repeatedCombos as $combo => $cnt): ?>
      <div class="info-row">
        <div class="info-text">
          <?php
            $digits = explode(',', $combo);
            foreach ($digits as $d) {
              echo '<span class="combo-square">' . htmlspecialchars(trim($d)) . '</span>';
            }
          ?>
          — <b><?= $cnt ?></b>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ===== МОДАЛКА БЕЗ JS (через :target) ===== -->
<style>
  /* По умолчанию скрыто */
  #verifierModal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.45);
    /* расположение контейнера модалки: сверху по центру */
    align-items: flex-start;
    justify-content: center;
    padding: 40px 20px 20px;
    z-index: 9999;
  }
  /* Показать, когда #verifierModal в адресной строке (…#verifierModal) */
  #verifierModal:target { display: flex; }

  #verifierModal .modal-dialog {
    background: rgba(255, 255, 255, 0.46); /* полупрозрачный белый */
    border-radius: 12px;
    width: min(420px, 48vw);
    height: min(687px, 76vh);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,.35);

    /* центр по умолчанию — чтобы всегда возвращалась в центр при открытии */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(calc(-50% + var(--tx, 0px)), calc(-50% + var(--ty, 0px)));
    margin: 0;

    /* страхующие ограничения размеров */
    max-width: calc(100vw - 20px);
    max-height: calc(100vh - 20px);
    overflow: auto;
  }

  #verifierModal .modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-bottom: 1px solid #eee; background: #f8f9fb;
    cursor: move; /* можно тянуть за шапку */
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
    pointer-events: auto;
  }
</style>


<div id="verifierModal" role="dialog" aria-modal="true" aria-labelledby="verifierTitle" tabindex="-1" onkeydown="if(event.key==='Escape'||event.key==='Esc'){location.hash='#tout-root';}">
  <div class="modal-dialog">
    <div class="modal-header" onmousedown="(function(e){
    if (e.button !== 0) return;                // только ЛКМ
    if (e.target.closest('.modal-close')) return;
    const d=document.querySelector('#verifierModal .modal-dialog');
    if(!d) return;

    let dragging = false;
    const startX = e.clientX, startY = e.clientY;
    const vw = window.innerWidth, vh = window.innerHeight;
    const dlgW = d.offsetWidth, dlgH = d.offsetHeight;
    const margin = 10;
    const THRESH = 3; // пикселей для начала драга

    // Текущие смещения по CSS-переменным
    const cs = getComputedStyle(d);
    const baseTx = parseFloat(cs.getPropertyValue('--tx')) || 0;
    const baseTy = parseFloat(cs.getPropertyValue('--ty')) || 0;

    function clampLeftTop(left, top){
      const minL = margin, minT = margin;
      const maxL = Math.max(minL, vw - dlgW - margin);
      const maxT = Math.max(minT, vh - dlgH - margin);
      if (left < minL) left = minL;
      if (top  < minT) top  = minT;
      if (left > maxL) left = maxL;
      if (top  > maxT) top  = maxT;
      return [left, top];
    }

    function mm(ev){
      const dx = ev.clientX - startX;
      const dy = ev.clientY - startY;

      if (!dragging) {
        if (Math.abs(dx) < THRESH && Math.abs(dy) < THRESH) return;
        dragging = true;
        document.body.style.userSelect='none';
        e.currentTarget.style.cursor='grabbing';
      }

      // Базовая «центровка»: левый верх при translate(-50%,-50%)
      const baseLeft = (vw - dlgW) / 2;
      const baseTop  = (vh - dlgH) / 2;

      // Желаемая новая позиция с учётом прошлого смещения и текущего dx/dy
      let left = baseLeft + baseTx + dx;
      let top  = baseTop  + baseTy + dy;

      // Ограничиваем в пределах вьюпорта
      [left, top] = clampLeftTop(left, top);

      // Конвертируем обратно в смещения относительно центра
      const newTx = left - baseLeft;
      const newTy = top  - baseTop;

      d.style.setProperty('--tx', newTx + 'px');
      d.style.setProperty('--ty', newTy + 'px');
    }

    function mu(){
      document.removeEventListener('mousemove', mm);
      window.removeEventListener('mouseup', mu);
      if (dragging) {
        document.body.style.userSelect='';
        e.currentTarget.style.cursor='move';
      }
    }

    document.addEventListener('mousemove', mm);
    window.addEventListener('mouseup', mu);
    // Не делаем preventDefault на mousedown — не ломаем клики в iframe
  })(event)">
      <h3 id="verifierTitle">Проверка комбинации</h3>
      <!-- Закрыть: возвращаемся к #tout-root (любой элемент-якорь в этом фрагменте) -->
      <a href="#tout-root" class="modal-close" aria-label="Закрыть">×</a>
    </div>
    <div class="modal-body">
      <!-- Код грузится напрямую из verifier.php -->
      <iframe src="../outis/verifier.php" loading="lazy" referrerpolicy="no-referrer" onload="try{var w=this.contentWindow,d=w.document;d.addEventListener('keydown',function(ev){if(ev.key==='Escape'||ev.key==='Esc'){parent.location.hash='#tout-root';}},true);}catch(e){}"></iframe>
    </div>
  </div>
</div>
<script>
(function(){
  const MOD = '#verifierModal';
  function isOpen(){ return location.hash === MOD; }
  function close(){ location.hash = '#tout-root'; }
  function focusModal(){
    const m=document.getElementById('verifierModal');
    if(m){
      if(!m.hasAttribute('tabindex')) m.setAttribute('tabindex','-1');
      m.focus({preventScroll:true});
    }
  }
  // Esc в родителе
  document.addEventListener('keydown', function(ev){
    if ((ev.key === 'Escape' || ev.key === 'Esc') && isOpen()){
      close();
    }
  }, true);
  // Фокус при открытии по хэшу
  window.addEventListener('hashchange', function(){
    if (isOpen()) focusModal();
  });
  // Сообщение от iframe (если Esc в нём)
  window.addEventListener('message', function(ev){
    if (ev && ev.data && ev.data.type === 'closeVerifier') {
      close();
    }
  });
  // Если уже открыто при загрузке
  if (isOpen()) focusModal();
})();
</script>

