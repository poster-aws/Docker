<?php

// Подключение к БД toutourien
$conn = new mysqli("db", "user", "user", "toutourien");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Лимит по GET
$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits) ? (int)$_GET['limit'] : 50;

// Получение данных
$sqlGrid = "SELECT Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12 FROM Tout ORDER BY Tirage DESC LIMIT $limit";
$resGrid = $conn->query($sqlGrid);
$tirages = [];
if ($resGrid && $resGrid->num_rows > 0) {
    while ($r = $resGrid->fetch_assoc()) {
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tout Grid</title>
  <style>
    html, body {
      height: 100%;
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
      width: 95%;
      max-height: 70vh;
      overflow: auto;
      margin: 0 auto;
      border: 1px solid #ccc;
      background: rgba(173, 216, 230, 0.85);
    }

    table.digit-grid {
      width: max-content;
      border-collapse: collapse;
      table-layout: fixed;
      font-size: 12px;
    }

    .digit-grid td, .digit-grid th {
      width: 20px;
      height: 20px;
      text-align: center;
      border: 1px solid #ccc;
      padding: 0;
      box-sizing: border-box;
    }

    .digit-grid th {
      height: 60px;
      writing-mode: vertical-rl;
      transform: rotate(180deg);
      font-size: 0.7em;
      background: #eee;
    }

    .digit-grid td.hit {
      background-color: #7eb0ea;
    }

    .digit-grid td.repeat-2 {
      background-color: #f8c471;
    }

    .digit-grid td.repeat-3 {
      background-color: #e74c3c;
    }

    .digit-grid td:first-child,
    .digit-grid th:first-child {
      background-color: #eee;
      font-weight: bold;
      position: sticky;
      left: 0;
      z-index: 1;
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
  </style>
</head>
<body>

<div class="table-wrapper">
  <?php if (!empty($tirages)): ?>
    <table class="digit-grid">
      <thead>
        <tr>
          <th></th>
          <?php foreach ($tirages as $t): ?>
            <th><?= htmlspecialchars($t['Tirage']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php for ($digit = 1; $digit <= 24; $digit++): ?>
          <tr>
            <td><?= $digit ?></td>
            <?php foreach ($tirages as $t):
              $count = array_count_values($t['nums'])[$digit] ?? 0;
              $class = $count === 3 ? 'repeat-3' : ($count === 2 ? 'repeat-2' : ($count === 1 ? 'hit' : ''));
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
    <?php foreach ([50, 100, 200, 500] as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select> tirages
</form>

</body>
</html>