<!-- src/toutourien/Info/toutinfo.php -->

<?php
require_once "../db.php";

// Общее количество тиражей (всего)
$totalRes = $toutConn->query("SELECT COUNT(*) AS total FROM Tout");
$totalCount = ($totalRes && $row = $totalRes->fetch_assoc()) ? $row['total'] : '?';

// Лимит
$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits) ? (int)$_GET['limit'] : 50;

// Данные из БД для Analyse
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
        $r['n9'], $r['n10'], $r['n11'], $r['n12']
      ])
    ];
  }
}

/* === Построение сводной таблицы Analyse === */
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

/* === Подсчёт комбинаций из всей таблицы === */
$sqlAll = "SELECT n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout";
$resAll = $toutConn->query($sqlAll);

$comboSet = [];
$comboCounts = [];
$totalPossibleComb = 2704156; // задано как число всех возможных комбинаций

if ($resAll && $resAll->num_rows > 0) {
  while ($r = $resAll->fetch_assoc()) {
    $nums = array_map('intval', [
      $r['n1'], $r['n2'], $r['n3'], $r['n4'], $r['n5'], $r['n6'],
      $r['n7'], $r['n8'], $r['n9'], $r['n10'], $r['n11'], $r['n12']
    ]);
    sort($nums);
    $key = implode(',', $nums);

    $comboSet[$key] = true;
    if (!isset($comboCounts[$key])) $comboCounts[$key] = 0;
    $comboCounts[$key]++;
  }
}

// Подсчёт реально вышедших уникальных комбинаций
$totalActualComb = count($comboSet);

// Повторяющиеся комбинации
$repeatedCombos = array_filter($comboCounts, fn($cnt) => $cnt > 1);
arsort($repeatedCombos);

$toutConn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tout Info</title>
  <style>
    /* Стили остаются прежними, только без .digit-grid */

    .filter-form {
      text-align: center;
      margin: 0;
      padding: 5px 0;
    }

    .filter-form select {
      padding: 6px 10px;
      font-size: 14px;
    }

    .analyse-grid {
      background-color: rgba(255, 255, 255, 0);
    }

    .analyse-grid th,
    .analyse-grid td {
      width: 25px;
      height: 25px;
      border-radius: 4px;
      font-size: 12px;
      text-align: center;
      vertical-align: middle;
      background-color: #eeeeee;
      border: 1px solid #ccc;
      padding: 0;
    }

    .analyse-grid td.sticky-label {
      position: sticky;
      left: 0;
      background-color: #f0f0f0;
      z-index: 1;
      width: 25px;
      height: 25px;
      font-weight: bold;
      font-size: 12px;
      text-align: center;
    }

    .analyse-grid td.empty {
      background-color: #dcdbdbff;
    }

    .table-wrapper {
      max-width: 98%;
      max-height: 90vh;
      overflow: auto;
      margin: 0 auto;
      background: rgba(173, 216, 230, 0);
    }

    /* Перенесенные стили инфо-блока */
    .info-list {
      margin: 10px auto;
      max-width: 800px;
      font-size: 14px;
    }

    .info-row {
      display: flex;
      align-items: center;
      padding: 6px 10px;
      border-left: 4px solid #FF8C00;
      background: rgba(255, 255, 255, 0.5);
      margin-bottom: 8px;
      border-radius: 6px;
    }

    .info-text {
      flex: 1;
    }

    .combo-square {
      display: inline-block;
      min-width: 22px;
      height: 22px;
      line-height: 22px;
      text-align: center;
      margin-right: 4px;
      background: rgba(126,176,234,0.8);
      color: #000;
      border-radius: 4px;
      font-weight: 600;
    }
  </style>
</head>
<body>

<?php if (!empty($positionMatrix)): ?>
  <div class="table-wrapper" style="margin: 10px auto; text-align: center;">
    <table class="analyse-grid" style="margin: 0 auto;">
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
            <td class="sticky-label"><?= strtoupper($pos) ?></td>
            <?php foreach ($counts as $val): ?>
              <?php if ($val === 0): ?>
                <td class="empty"></td>
              <?php else: ?>
                <td><?= $val ?></td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p style="text-align:center; color: red;">Нет данных для отображения.</p>
<?php endif; ?>

<form class="filter-form" method="get">
  Dernières
  <select name="limit" onchange="this.form.submit()">
    <?php foreach ($allowedLimits as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select> tirages
</form>

<!-- === Инфо блок, перенесённый из tout.php === -->
<div class="info-list">
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
      <div class="info-text">Combinaisons sorties plusieurs fois:</div>
    </div>
    <?php foreach ($repeatedCombos as $combo => $cnt): ?>
      <div class="info-row">
        <div class="info-text">
          <?php foreach (explode(',', $combo) as $d): ?>
            <span class="combo-square"><?= htmlspecialchars(trim($d)) ?></span>
          <?php endforeach; ?>
          — <b><?= $cnt ?></b>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>