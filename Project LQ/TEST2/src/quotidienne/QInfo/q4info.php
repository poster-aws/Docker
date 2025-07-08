<?php
require_once "../db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isUniqueCombo($n1, $n2, $n3, $n4) {
    return count(array_unique([$n1, $n2, $n3, $n4])) === 4;
}

function getComboType($nums) {
    $count = array_count_values($nums);
    $values = array_values($count);
    rsort($values);

    if (count($count) === 4) return 'unique';
    if (count($count) === 3) return 'onepair';
    if (count($count) === 2 && in_array(2, $values) && $values[0] === 2) return 'twopairs';
    if ($values[0] === 3) return 'triplet';
    return 'other';
}

$fois1      = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois ");  //WHERE Fois = 1
$freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0");
$freeNorder = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0 and n1=n2 and n2=n3 and n3=n4");
// Все комбинации norder уже выпали, за исключением 1111 и 2222 поэтому берем из Q4_fois
// $freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_free_comb_order");
// $freeNorder = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_free_comb_norder");
// Q4_free_comb_order - подсчет комбинаций которые никогда не выпадали в order
// 4_free_comb_norder - подсчет комбинаций которые никогда не выпадали в norder

?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: sans-serif;
    }

    .tables-wrapper {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin: 2px auto 0 auto;
      max-width: 95%;
    }

    .table-container,
    .combo-table-container {
      width: max-content;
      max-height: 85vh;
      overflow: auto;
      border-radius: 12px;
      border: 2px solid #a4a1a1;
      background: #00000005;
      box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.3);
      scrollbar-width: none;
    }
    

    .number-stats-table {
      width: max-content;
      max-height: none;
      height: fit-content;
      display: inline-block; /* добавлено */
      overflow: hidden; /* либо auto, если хочешь скролл при переполнении */
      border-radius: 12px;
      border: 2px solid #a4a1a1;
      background: #00000005;
      box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.3);
      scrollbar-width: none;
    }

    .table-container::-webkit-scrollbar,
    .combo-table-container::-webkit-scrollbar,
    .number-stats-table::-webkit-scrollbar {
      width: 0px;
    }

    .interactive-table {
      border-collapse: collapse;
      font-size: 18px;
      font-family: 'Shadows Into Light', cursive;
      color: #000;
      width: 100%;
    }

    .interactive-table thead tr:nth-child(1) th {
      position: sticky;
      top: 0;
      background-color: rgb(163, 216, 234);
      z-index: 12;
      border-bottom: 1px solid #999;
      padding: 6px 4px;
    }

    .interactive-table thead tr:nth-child(2) th {
      position: sticky;
      top: 38px; /* высота первой строки заголовка */
      background-color: rgb(218, 238, 247);
      z-index: 11;
      padding: 2px 4px;
      border-top: none;
    }

    .interactive-table td {
      padding: 9px;
      border-bottom: 1px dashed #777;
      text-align: center;
      white-space: nowrap;
    }

    .interactive-table tr:hover {
      background-color: rgba(161, 161, 161, 0.493);
      transition: background-color 0.3s ease;
    }

    .interactive-table thead tr:nth-child(2) th {
      position: sticky;
      top: 38px;
      background-color: rgb(163, 216, 234); /* тот же, что у select */
      z-index: 11;
      padding: 2px 4px;
      border-top: none;
    }

    .highlight-row {
      background-color: rgba(221, 221, 221, 0.493);
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

    select {
      width: 100%;
      padding: 4px 8px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #007BFF;
      background-color: rgb(163, 216, 234);
      color: #000;
      box-sizing: border-box;
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
      font-family: Arial, sans-serif;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
    }
  </style>
</head>
<body>
<div class="tables-wrapper">

  <!-- Fois = 1 -->
  <div class="table-container">
    <table class="interactive-table" id="statsOrderTable">
      <thead>
        <tr><th colspan="4"> <span id="statsOrderCount"></span></th></tr>
        <tr><th colspan="4">
          <select onchange="applyFilter('statsOrderTable', this.value)">
            <option value="all">*Dans l'ordre (toutes)</option>
            <option value="unique">- Tous les numéros sont différents</option>
            <option value="onepair">- Une paire</option>
            <option value="twopairs">- Deux paires</option>
            <option value="triplet">- Trois identiques + Un différent</option>
          </select>
        </th></tr>
      </thead>
      <tbody>
        <?php while ($row = $fois1->fetch_assoc()):
          $comboType = getComboType([$row['n1'], $row['n2'], $row['n3'], $row['n4']]); ?>
          <tr data-combo-type="<?= $comboType ?>" class="<?= isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '' ?>">
            <td>
              <span class="circle"><?= $row['n1'] ?></span>
              <span class="circle"><?= $row['n2'] ?></span>
              <span class="circle"><?= $row['n3'] ?></span>
              <span class="circle"><?= $row['n4'] ?></span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Fois = 0 -->
  <div class="combo-table-container">
    <table class="interactive-table" id="freeOrderTable">
      <thead>
        <tr><th colspan="4"> <span id="freeOrderCount"></span></th></tr>
        <tr><th colspan="4">
          <select onchange="applyFilter('freeOrderTable', this.value)">
            <option value="all">*Dans l'ordre (toutes)</option>
            <option value="unique">- Tous les numéros sont différents</option>
            <option value="onepair">- Une paire</option>
            <option value="twopairs">- Deux paires</option>
            <option value="triplet">- Trois identiques + Un différent</option>
          </select>
        </th></tr>
      </thead>
      <tbody>
        <?php while ($row = $freeOrder->fetch_assoc()):
          $comboType = getComboType([$row['n1'], $row['n2'], $row['n3'], $row['n4']]); ?>
          <tr data-combo-type="<?= $comboType ?>" class="<?= isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '' ?>">
            <td>
              <span class="circle"><?= $row['n1'] ?></span>
              <span class="circle"><?= $row['n2'] ?></span>
              <span class="circle"><?= $row['n3'] ?></span>
              <span class="circle"><?= $row['n4'] ?></span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Q4_free_comb_norder -->
  <div class="number-stats-table">
    <table class="interactive-table">
      <thead>
        <tr><th colspan="4">Jamais sorti <br> n'inport quel ordre</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $freeNorder->fetch_assoc()): ?>
          <tr class="<?= isUniqueCombo($row['n1'], $row['n2'], $row['n3'], $row['n4']) ? 'highlight-row' : '' ?>">
            <td>
              <span class="circle"><?= $row['n1'] ?></span>
              <span class="circle"><?= $row['n2'] ?></span>
              <span class="circle"><?= $row['n3'] ?></span>
              <span class="circle"><?= $row['n4'] ?></span>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>


<!-- Информационный блок -->
<div id="infoBlock" class="info-list">
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">9</span><span class="circle">9</span><span class="circle">9</span><span class="circle">9</span>
    </div>
    <div class="info-text">Dans l'Order - Toutes les combinaisons : <b>10'000</b> </div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">3</span><span class="circle">4</span>
    </div>
    <div class="info-text">Dans l'Order - Tous les numéros sont différents : <b>5'040</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">1</span><span class="circle">2</span><span class="circle">3</span>
    </div>
    <div class="info-text">Dans l'Order - Une paire : <b>4'320</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">2</span>
    </div>
    <div class="info-text">Dans l'Order - Deux paires : <b>270</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">1</span><span class="circle">1</span><span class="circle">8</span>
    </div>
    <div class="info-text">Dans l'Order - Trois identiques + Une différent : <b>360</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">8</span><span class="circle">8</span><span class="circle">8</span><span class="circle">8</span>
    </div>
    <div class="info-text">N'importe – sans doublons – <b>120</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">8</span><span class="circle">8</span><span class="circle">8</span><span class="circle">8</span>
    </div>
    <div class="info-text">N'importe – seulement doublons – <b>90</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">8</span><span class="circle">8</span><span class="circle">8</span><span class="circle">8</span>
    </div>
    <div class="info-text">Trois identiques – <b>10</b> combinaisons</div>
  </div>
</div>
<!-- Информационный блок конец-->

<script>
function applyFilter(tableId, filterValue) {
  const table = document.getElementById(tableId);
  const rows = table.querySelectorAll("tbody tr");
  let count = 0;

  rows.forEach(row => {
    const type = row.dataset.comboType;
    const visible = (filterValue === 'all' || filterValue === type);
    row.style.display = visible ? '' : 'none';
    if (visible) count++;
  });

  const header = table.querySelector("thead tr:first-child th");
  const baseTitle = tableId === "statsOrderTable"
    ? "Sorti une fois : "
    : "Jamais sorti : ";
  header.innerHTML = `${baseTitle} <span> <strong>${count}</strong> combs.</span>`;
}

window.addEventListener('DOMContentLoaded', () => {
  applyFilter('statsOrderTable', 'all');
  applyFilter('freeOrderTable', 'all');
});
</script>

</body>
</html>