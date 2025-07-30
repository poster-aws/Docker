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
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: sans-serif;
      overflow-y: auto;
      scrollbar-width: none; /* Firefox */
    }

    body::-webkit-scrollbar {
      display: none; /* Chrome, Safari */
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

    .circle {
      display: inline-block;
      width: 28px;
      height: 28px;
      line-height: 28px;
      border-radius: 50%;
      background-color: #7eb0ea;
      color: #000;
      font-weight: bold;
      text-align: center;
      font-family: Arial, sans-serif;
      margin: 0 3px;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
    }

#infoBlock.info-list {
      display: flex;
      flex-direction: column;
      padding: 14px 16px;
      gap: 8px;
      font-size: 0.95em;
      max-width: 800px;
      margin: 30px auto;
      background: rgba(255,255,255,0.03);
      color: #333;
    }

    .info-row {
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 4px solid #FF8C00;
      padding-left: 10px;
      background: rgba(255, 255, 255, 0.26);
      border-radius: 6px;
    }

    .info-text {
      font-size: 0.95em;
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

<!-- <h2>Сетка появления цифр по тиражам</h2> -->

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
  Dernières
  <select name="limit" onchange="this.form.submit()">
    <?php foreach ([50, 100, 200, 500] as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select> tirages
</form>

<!-- Информационный блок -->
<div id="infoBlock" class="info-list">
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span>
      <span class="circle">2</span>
      <span class="circle">3</span>
    </div>
    <div class="info-text">Dans l'Order - Toutes les combinaisons : <b>1'000</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">4</span>
      <span class="circle">5</span>
      <span class="circle">6</span>
    </div>
    <div class="info-text">Dans l'Order - Tous les numéros sont différents : <b>720</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span>
      <span class="circle">1</span>
      <span class="circle">2</span>
    </div>
    <div class="info-text">Dans l'Order - Une paire : <b>270</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">3</span>
      <span class="circle">1</span>
      <span class="circle">2</span>
    </div>
    <div class="info-text">N'Importe quel Order - Toutes les combinaisons : <b>220</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">3</span>
      <span class="circle">1</span>
      <span class="circle">2</span>
    </div>
    <div class="info-text">N'Importe quel Order - Tous les numéros sont différents : <b>120</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span>
      <span class="circle">2</span>
      <span class="circle">1</span>
    </div>
    <div class="info-text">N'Importe quel Order - Une paire : <b>90</b> </div>
  </div>

  <div class="info-row">
    <div class="info-digits">
      <span class="circle">7</span>
      <span class="circle">7</span>
      <span class="circle">7</span>
    </div>
    <div class="info-text">Trois numéros identiques : <b>10</b> </div>
  </div>
</div>

</body>
</html>