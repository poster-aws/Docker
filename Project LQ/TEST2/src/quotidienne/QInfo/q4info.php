<!-- q4info.php -->

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

$freeOrder  = $conn->query("SELECT n1, n2, n3, n4 FROM Q4_fois WHERE Fois = 0");

// Fetch "all digits the same" with Fois = 0
$allSameZero = $conn->query("
  SELECT n1
  FROM Q4_fois
  WHERE Fois = 0 AND n1 = n2 AND n2 = n3 AND n3 = n4
  GROUP BY n1
  ORDER BY n1
");

/* === Вариант А: группы среди fois=0, где есть минимум 2 перестановки одной мультикомбинации ===
   rep_key  — любой представитель группы (минимальная строка n1n2n3n4);
   cnt      — число перестановок в группе среди fois=0;
   members  — список всех перестановок (как строки n1n2n3n4) для выпадающего окна.
*/

$dupsA = $conn->query("
  SELECT
    MIN(CONCAT(n1,n2,n3,n4)) AS rep_key,
    COUNT(*)                 AS cnt,
    GROUP_CONCAT(CONCAT(n1,n2,n3,n4) ORDER BY n1,n2,n3,n4 SEPARATOR ',') AS members
  FROM Q4_fois
  WHERE Fois = 0
  GROUP BY CONCAT_WS('|',
    (n1=0)+(n2=0)+(n3=0)+(n4=0),
    (n1=1)+(n2=1)+(n3=1)+(n4=1),
    (n1=2)+(n2=2)+(n3=2)+(n4=2),
    (n1=3)+(n2=3)+(n3=3)+(n4=3),
    (n1=4)+(n2=4)+(n3=4)+(n4=4),
    (n1=5)+(n2=5)+(n3=5)+(n4=5),
    (n1=6)+(n2=6)+(n3=6)+(n4=6),
    (n1=7)+(n2=7)+(n3=7)+(n4=7),
    (n1=8)+(n2=8)+(n3=8)+(n4=8),
    (n1=9)+(n2=9)+(n3=9)+(n4=9)
  )
  HAVING COUNT(*) >= 2
  ORDER BY cnt DESC
");

function key_to_digits($key4) {
    return [
        (int)substr($key4, 0, 1),
        (int)substr($key4, 1, 1),
        (int)substr($key4, 2, 1),
        (int)substr($key4, 3, 1)
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  font-family: sans-serif;
  overflow-y: auto;
  scrollbar-width: none; /* Firefox */
}
body::-webkit-scrollbar { display: none; }

.tables-wrapper {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  margin: 2px auto 0 auto;
  max-width: 95%;
}

.table-container,
.combo-table-container,
.number-stats-table {
  width: max-content;
  max-height: 85vh;
  overflow: auto;
  border-radius: 12px;
  border: 2px solid #a4a1a1;
  background: #00000005;
  box-shadow: 2px 4px 6px rgba(0,0,0,0.3);
  scrollbar-width: none;
}
.table-container::-webkit-scrollbar,
.combo-table-container::-webkit-scrollbar,
.number-stats-table::-webkit-scrollbar { width: 0px; }

.interactive-table {
  border-collapse: collapse;
  font-size: 18px;
  font-family: 'Shadows Into Light', cursive;
  color: #000;
  width: 100%;
}
.interactive-table thead tr:nth-child(1) th {
  position: sticky; top: 0;
  background-color: rgb(163, 216, 234);
  z-index: 12; border-bottom: 1px solid #999; padding: 6px 4px;
}
.interactive-table thead tr:nth-child(2) th {
  position: sticky; top: 38px;
  background-color: rgb(218, 238, 247);
  z-index: 11; padding: 2px 4px; border-top: none;
}
.interactive-table td {
  padding: 9px; border-bottom: 1px dashed #777;
  text-align: center; white-space: nowrap;
}
.interactive-table tr:hover { background-color: rgba(161,161,161,0.493); transition: background-color .3s ease; }
.highlight-row { background-color: rgba(221,221,221,0.493); }

.circle {
  display: inline-block; width: 28px; height: 28px; line-height: 28px;
  border-radius: 50%; background-color: #7eb0ea; color: #000; font-weight: bold;
  text-align: center; font-family: Arial, sans-serif; margin: 0 3px;
  box-shadow: 0 0 3px rgba(0,0,0,0.4);
}

select {
  width: 100%; padding: 4px 8px; font-size: 16px; border-radius: 6px;
  border: 1px solid #007BFF; background-color: rgb(163, 216, 234); color: #000; box-sizing: border-box;
}

/* Кнопка-счётчик и выпадающее окно */
.count-btn {
  width: 2.5em;
  height: 2.5em;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  box-sizing: border-box;

  border: 1px solid #0b6efd;
  border-radius: 8px;
  background: #f0f0f0;
  cursor: pointer;
  font-weight: 700;

  padding: 0;
  line-height: 1;
}
.count-btn[disabled] { opacity: 0.6; cursor: default; }
.dropdown {
  position: relative; display: inline-block;
}
/* было:
.dropdown-panel {
  display: none; position: absolute; right: 0; top: 110%;
  min-width: 260px; max-height: 50vh; overflow: auto;
  background: #fff; border: 1px solid #aaa; border-radius: 10px; padding: 8px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2); z-index: 50; text-align: left;
}
*/

.dropdown-panel {
  display: none;
  position: absolute;
  right: 0;
  top: 110%;

  /* ключевые изменения: ширина по контенту */
  width: max-content;          /* основной вариант */
  max-width: 85vw;             /* чтобы не вылазило за экран */
  min-width: unset;            /* убрать фиксированный минимум */

  max-height: 50vh;
  overflow-y: auto;
  overflow-x: auto;            /* на всякий случай, если контент широкий */

  background: #f0f0f0; /* Светло-серый цвет выпадающего меню */
  border: 1px solid #aaa;
  border-radius: 10px;
  padding: 6px 8px;            /* чуть меньше, чтобы не раздувать */
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  z-index: 50;
  text-align: left;
}

.member-item {
  padding: 4px 0;
  border-bottom: 1px dashed #ccc;
  white-space: nowrap;         /* не переносить шары на новую строку */
}
.member-item:last-child { border-bottom: none; }
.dropdown-panel.open { display: block; }
.member-item { padding: 4px 0; border-bottom: 1px dashed #ccc; }
.member-item:last-child { border-bottom: none; }

#infoBlock.info-list {
  display: flex; flex-direction: column; padding: 14px 16px; gap: 8px; font-size: .95em;
  max-width: 800px; margin: 30px auto; background: rgba(255,255,255,0.03); color: #333;
}
.info-row { display: flex; align-items: center; gap: 12px; border-left: 4px solid #FF8C00; padding-left: 10px; background: rgba(255,255,255,0.26); border-radius: 6px;}
.info-text { font-size: .95em; }
.digit { display: inline-flex; width: 20px; height: 20px; margin-right: 5px; border-radius: 50%;
  background-color: #7eb0ea; color: #000; font-weight: bold; justify-content: center; align-items: center;
  font-family: Arial, sans-serif; box-shadow: 0 0 3px rgba(0,0,0,0.4);
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
            <option value="unique">- Numéros sont différents</option>
            <option value="onepair">- Une paire</option>
            <option value="twopairs">- Deux paires</option>
            <option value="triplet">- Trois identiques</option>
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
            <option value="unique">- Numéros sont différents</option>
            <option value="onepair">- Une paire</option>
            <option value="twopairs">- Deux paires</option>
            <option value="triplet">- Trois identiques</option>
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

  <!-- === Новая третья таблица: «Вариант А» вместо старой === -->
  <div class="number-stats-table">
    <table class="interactive-table" id="dupsVariantATable">
      <thead>
        <tr><th colspan="5">Jamais sorti <br> N'Inport quel Ordre</th></tr>
        <!-- <tr>
          <th>n1</th><th>n2</th><th>n3</th><th>n4</th><th>count</th>
        </tr> -->
      </thead>
      <tbody>
        <?php
        // Render "all digits the same" with Fois=0 at the top
        ?>
        <?php if (isset($allSameZero) && $allSameZero && $allSameZero->num_rows > 0): ?>
          <?php while ($s = $allSameZero->fetch_assoc()):
            $d = (int)$s['n1']; ?>
            <tr>
              <td>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
                <span class="circle"><?= $d ?></span>
              </td>
              <td>
                <button class="count-btn" disabled>-</button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
        <?php
        $rowIndex = 0;
        while ($r = $dupsA->fetch_assoc()):
          $rowIndex++;
          [$a,$b,$c,$d] = key_to_digits($r['rep_key']);
          $cnt = (int)$r['cnt'];
          $members = array_filter(explode(',', $r['members']), fn($s) => $s !== '');
        ?>
        <tr>
          <td>
            <span class="circle"><?= $a ?></span>
            <span class="circle"><?= $b ?></span>
            <span class="circle"><?= $c ?></span>
            <span class="circle"><?= $d ?></span>
          </td>
          <td>
            <div class="dropdown">
              <button class="count-btn" onclick="toggleMembers(event, 'm<?= $rowIndex ?>')"><?= $cnt ?></button>
              <div class="dropdown-panel" id="m<?= $rowIndex ?>">
                <?php foreach ($members as $mk):
                  $da = (int)substr($mk,0,1);
                  $db = (int)substr($mk,1,1);
                  $dc = (int)substr($mk,2,1);
                  $dd = (int)substr($mk,3,1);
                ?>
                  <div class="member-item">
                    <span class="circle"><?= $da ?></span>
                    <span class="circle"><?= $db ?></span>
                    <span class="circle"><?= $dc ?></span>
                    <span class="circle"><?= $dd ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<!-- Информационный блок (без изменений) -->
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
    <div class="info-text">Dans l'Order - Trois identiques : <b>360</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">8</span><span class="circle">8</span><span class="circle">8</span><span class="circle">8</span>
    </div>
    <div class="info-text">N'Importe quel Order - Toutes les combinaisons : <b>715</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">4</span><span class="circle">3</span><span class="circle">2</span><span class="circle">1</span>
    </div>
    <div class="info-text">N'Importe quel Order - Tous les numéros sont différents : <b>210</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">3</span><span class="circle">1</span>
    </div>
    <div class="info-text">N'Importe quel Order - Une paire : <b>360</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">2</span>
    </div>
    <div class="info-text">N'Importe quel Order - Deux пaires : <b>45</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">1</span><span class="circle">2</span><span class="circle">1</span><span class="circle">1</span>
    </div>
    <div class="info-text">N'Importe quel Order - Trois identiques : <b>90</b></div>
  </div>
  <div class="info-row">
    <div class="info-digits">
      <span class="circle">7</span><span class="circle">7</span><span class="circle">7</span><span class="circle">7</span>
    </div>
    <div class="info-text">Quatre identiques – <b>10</b> </div>
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
  const baseTitle = tableId === "statsOrderTable" ? "Sorti une fois : " : "Jamais sorti : ";
  header.innerHTML = `${baseTitle} <span> <strong>${count}</strong> combs.</span>`;
}

function toggleMembers(evt, id) {
  evt.stopPropagation();
  // закрыть все открытые
  document.querySelectorAll('.dropdown-panel.open').forEach(p => p.classList.remove('open'));
  // открыть текущий
  const panel = document.getElementById(id);
  if (panel) panel.classList.add('open');
}
document.addEventListener('click', () => {
  document.querySelectorAll('.dropdown-panel.open').forEach(p => p.classList.remove('open'));
});

window.addEventListener('DOMContentLoaded', () => {
  applyFilter('statsOrderTable', 'all');
  applyFilter('freeOrderTable', 'all');
});
</script>

</body>
</html>

<!-- END of q4info.php -->