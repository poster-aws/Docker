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

$fois1      = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 1");
// $freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_free_comb_order");
$freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0");
$freeNorder = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_free_comb_norder");
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
      flex-direction: row;
      justify-content: space-between;
      align-items: flex-start;
      gap: 20px;
      flex-wrap: nowrap;
      margin: 1.8px auto 0 auto;
      max-width: 95%;
    }

    .table-container,
    .combo-table-container,
    .number-stats-table {
      flex: 0 0 auto;
      width: max-content;
      max-height: 85vh;
      overflow-x: auto;
      overflow-y: auto;
      margin-inline: auto;
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
      background: transparent;
    }

    .interactive-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 18px;
      font-family: 'Shadows Into Light', cursive;
      color: #000000;
      scrollbar-width: thin;
      scrollbar-color: rgb(30, 0, 255) transparent;
    }

    .interactive-table thead th {
      position: sticky;
      top: 0;
      background-color: rgb(163, 216, 234);
      z-index: 10;
      border-bottom: 2px solid #888;
    }

    .interactive-table thead tr:nth-child(2) th {
      padding: 2px 4px;
      top: 44px;
      background-color: rgb(218, 238, 247);
    }

    .interactive-table td {
      padding: 9px;
      border-bottom: 1px dashed #777777;
      text-align: center;
      white-space: nowrap;
    }

    .interactive-table tr:hover {
      background-color: rgba(161, 161, 161, 0.493);
      transition: background-color 0.3s ease;
    }

    .interactive-table thead tr {
      line-height: 1;
      padding: 0;
      margin: 0;
    }

    .interactive-table thead th {
      border-collapse: collapse;
      margin: 0;
      padding-top: 6px;
      padding-bottom: 6px;
    }

    .interactive-table thead tr:nth-child(2) th {
      padding-top: 0;
      padding-bottom: 4px;
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
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
      font-family: Arial, sans-serif;
      margin: 0 3px;
    }

    select {
      width: 100%;
      padding: 4px 8px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #007BFF;
      background-color: #f1f9ff;
      color: #000;
      box-sizing: border-box;
    }

    #infoBlock {
      max-width: 800px;
      margin: 30px auto;
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
<div class="tables-wrapper">
  <div class="table-container">
    <table class="interactive-table" id="statsOrderTable">
      <thead>
        <tr><th colspan="4">Q4_stats_order — Fois = 1 <span id="statsOrderCount"></span></th></tr>
        <tr><th colspan="4">
          <select onchange="applyFilter('statsOrderTable', this.value)">
            <option value="all">Все</option>
            <option value="unique">Все цифры разные</option>
            <option value="onepair">Одна пара</option>
            <option value="twopairs">Две пары</option>
            <option value="triplet">Тройка</option>
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

  <div class="combo-table-container">
    <table class="interactive-table" id="freeOrderTable">
      <thead>
        <tr><th colspan="4">Q4_free_comb_order <span id="freeOrderCount"></span></th></tr>
        <tr><th colspan="4">
          <select onchange="applyFilter('freeOrderTable', this.value)">
            <option value="all">Все</option>
            <option value="unique">Все цифры разные</option>
            <option value="onepair">Одна пара</option>
            <option value="twopairs">Две пары</option>
            <option value="triplet">Тройка</option>
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

  <div class="number-stats-table">
    <table class="interactive-table">
      <thead>
        <tr><th colspan="4">Q4_free_comb_norder</th></tr>
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

<div id="infoBlock">
  <p>
    <span class="digit">8</span>
    <span class="digit">8</span>
    <span class="digit">8</span>
    <span class="digit">8</span>
    L'information à venir
  </p>
</div>

<script>
function getComboType(nums) {
  const count = {};
  nums.forEach(n => count[n] = (count[n] || 0) + 1);
  const values = Object.values(count).sort((a, b) => b - a);

  if (values.length === 4) return 'unique';
  if (values.length === 3) return 'onepair';
  if (values.length === 2 && values.includes(2) && values[0] === 2) return 'twopairs';
  if (values[0] === 3) return 'triplet';
  return 'other';
}

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

  // Обновление счётчика в заголовке
  const header = table.querySelector("thead tr:first-child th");
  const baseTitle = tableId === "statsOrderTable"
    ? " Sorti une fois : "
    : " Jamais sorti : ";
  header.textContent = `${baseTitle} ${count} comb. `;
}

// Инициализация при загрузке
window.addEventListener('DOMContentLoaded', () => {
  applyFilter('statsOrderTable', 'all');
  applyFilter('freeOrderTable', 'all');
});
</script>
</body>
</html>