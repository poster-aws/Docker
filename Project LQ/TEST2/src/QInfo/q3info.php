<?php
require_once "../db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Лимит по GET
$allowedLimits = [50, 100, 200, 500];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits) ? (int)$_GET['limit'] : 50;

// Получение данных
$sqlGrid = "SELECT Tirage, n1, n2, n3 FROM Q3 ORDER BY Tirage DESC LIMIT $limit";
$resGrid = $conn->query($sqlGrid);
$tirages = [];
if ($resGrid && $resGrid->num_rows > 0) {
    while ($r = $resGrid->fetch_assoc()) {
        $tirages[] = [
            'Tirage' => $r['Tirage'],
            'nums' => [$r['n1'], $r['n2'], $r['n3']]
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
  <title>Q3 Grid</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: sans-serif;
      background: rgba(245, 245, 245, 0);
    }

    h2 {
      text-align: center;
      margin: 12px 0;
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

    #infoBlock {
      max-width: 800px;
      margin: 16px auto 40px;
      padding: 8px 16px;
      background: rgba(245, 245, 245, 0);
      border-left: 4px solid #007BFF;
      font-size: 0.95em;
      line-height: 1.3;
      color: #333;
    }

    .digit {
      display: inline-flex;
      width: 20px;
      height: 20px;
      margin-right: 5px;
      border-radius: 50%;
      background-color: #7eb0ea;
      color: #000;
      font-weight: bold;
      justify-content: center;
      align-items: center;
      text-align: center;
      font-family: Arial, sans-serif;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
    }
  </style>
</head>
<body>

<h2>Сетка появления цифр по тиражам</h2>

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
        <?php for ($digit = 0; $digit <= 9; $digit++): ?>
          <tr>
            <td><?= $digit ?></td>
            <?php foreach ($tirages as $t):
              $count = array_count_values($t['nums'])[$digit] ?? 0;
              $class = $count === 2 ? 'repeat-2' : ($count === 3 ? 'repeat-3' : ($count === 1 ? 'hit' : ''));
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
  Показать последних:
  <select name="limit" onchange="this.form.submit()">
    <?php foreach ([50, 100, 200, 500] as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select> тиражей
</form>

<div id="infoBlock">
  <p>
    <span class="digit">1</span>
    <span class="digit">2</span>
    <span class="digit">3</span>
    Nombre de combinaisons dans Order – 1000
  </p>
  <p>
    <span class="digit">1</span>
    <span class="digit">3</span>
    <span class="digit">2</span>
    Nombre de combinaisons dans N'importe quel order – ???
  </p>
  <p>
    <span class="digit">2</span>
    <span class="digit">1</span>
    <span class="digit">2</span>
    Nombre de combinaisons dans N'importe quel order avec doublons ?
  </p>
</div>

</body>
</html>