<!-- src/toutourien/Info/tout.php -->

<?php
require_once "../db.php";

// Общее количество тиражей (всего)
$totalRes = $toutConn->query("SELECT COUNT(*) AS total FROM Tout");
$totalCount = ($totalRes && $row = $totalRes->fetch_assoc()) ? $row['total'] : '?';

// Лимит
$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits) ? (int)$_GET['limit'] : 50;

// Данные из БД
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
$toutConn->close();

// Подсчёт Tot
$totals = array_fill(1, 24, 0);
foreach ($tirages as $t) {
  foreach ($t['nums'] as $num) {
    if ($num >= 1 && $num <= 24) {
      $totals[$num]++;
    }
  }
}

/* === НОВЫЙ КОД: построение сводной таблицы Analyse === */
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tout Grid</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: sans-serif;
      overflow-y: auto;
      scrollbar-width: none;
    }

    body::-webkit-scrollbar {
      display: none;
    }

    .table-wrapper {
      max-width: 98%;
      max-height: 90vh;
      overflow: auto;
      margin: 0 auto;
      /* border: 1px solid #ccc; */
      background: rgba(173, 216, 230, 0);
    }

    table.digit-grid {
      width: max-content;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 12px;
    }

    .digit-grid th,
    .digit-grid td {
      width: 22px;
      height: 22px;
      text-align: center;
      border: 1px solid #ccc;
      padding: 0;
      box-sizing: border-box;
    }

    .digit-grid th {
      writing-mode: vertical-rl;
      /* transform: rotate(180deg); */
      white-space: nowrap;
      height: 80px;
      min-width: 20px;
      background-color: #eee;
    }
    .vertical-text {
      writing-mode: vertical-rl;
      transform: rotate(180deg);
      white-space: nowrap;
    }

    .digit-grid td.hit {
      background-color: #7eb0ea;
    }

    /* sticky: объединённая колонка */
    .digit-grid th:first-child,
    .digit-grid td.sticky-label {
      position: sticky;
      left: 0;
      background-color: #eee;
      z-index: 2;
      width: 40px;
      font-weight: normal;
      font-size: 0.95em;
      text-align: left;
      padding-left: 2px;
      white-space: nowrap;
    }

    .sticky-label .tot {
      color: red;
      font-weight: normal;
    }
    .sticky-label .digit-num {
      font-weight: bold !important;
      color: black;
    }

    .sticky-label .tot.low {
      color: #999;
    }

    .sticky-label .tot.medium {
      color: #e67e22;
    }

    .sticky-label .tot.high {
      color: #c0392b;
      font-weight: bold;
    }

    .filter-form {
      text-align: center;
      margin: 0;
      padding: 10px 0;
    }

    .filter-form select {
      padding: 6px 10px;
      font-size: 14px;
    }

    /* === CSS: для второй таблицы Analyse ===  */

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

  </style>
</head>
<body>

<div class="table-wrapper">
  <?php if (!empty($tirages)): ?>
    <table class="digit-grid">
      <thead>
        <tr>
          <th>Tot #</th>
          <?php foreach ($tirages as $t): ?>
            <th><div class="vertical-text"><?= htmlspecialchars($t['Tirage']) ?></div></th>
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
          <tr>
            <?php
              $val = $totals[$digit];
              $classTot = $val >= $thresholdHigh ? 'high' : ($val >= $thresholdMedium ? 'medium' : 'low');
            ?>
            <td class="sticky-label"><span class="tot <?= $classTot ?>">+<?= $val ?></span> <span class="digit-num"><?= $digit ?></span></td>
            <?php foreach ($tirages as $t):
              $count = array_count_values($t['nums'])[$digit] ?? 0;
              $class = $count === 1 ? 'hit' : '';
            ?>
              <td class="<?= $class ?>"><?= $count > 0 ? $digit : '' ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center; color: red;">Нет данных для отображения.</p>
  <?php endif; ?>
</div>

<form class="filter-form" method="get">
  Dernières
  <select name="limit" onchange="this.form.submit()">
    <?php foreach ($allowedLimits as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select> tirages
</form>

<!-- === ОБНОВЛЁННЫЙ HTML: таблица Analyse ниже фильтра === -->
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
<?php endif; ?>

</body>
</html>