<?php
require_once "../db.php"; // $bancoConn

// -------------------------------
// 1. Читаем параметры меню
// -------------------------------
$type  = isset($_GET['type'])  ? $_GET['type']  : 'c2';      // c2 | c3
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'dernier'; // dernier | tous

// -------------------------------
// 2. Определяем таблицу comb2 / comb3
// -------------------------------
if ($type === 'c2') {
    $table = "comb2";
    $fields = ['n1', 'n2'];
} else {
    $table = "comb3";
    $fields = ['n1', 'n2', 'n3'];
}

// -------------------------------
// 3. Строим SQL в зависимости от scope
// -------------------------------
if ($scope === 'dernier') {
    $sql = "SELECT * FROM $table WHERE days = 0 ORDER BY Tirage DESC";
} else {
    $sql = "SELECT * FROM $table ORDER BY Tirage DESC";
}

// -------------------------------
// 4. Загружаем данные
// -------------------------------
$rows = [];
$result = $bancoConn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
}

// === Получаем последний тираж Banco (20 чисел) ===
$lastNums = [];
$sqlLast = "SELECT * FROM banco ORDER BY Tirage DESC LIMIT 1";
$resLast = $bancoConn->query($sqlLast);
if ($resLast && $resLast->num_rows > 0) {
    $rowLast = $resLast->fetch_assoc();

    // собрать n1 … n20
    for ($i = 1; $i <= 20; $i++) {
        $lastNums[] = $rowLast["n$i"];
    }
}

// === Подбираем текст строки №2 ===
$comboText = "";

if ($type === "c2" && $scope === "dernier") {
    $comboText = "Toutes les combinaisons – 190";
}
elseif ($type === "c2" && $scope === "tous") {
    $comboText = "Toutes les combinaisons – 2 415";
}
elseif ($type === "c3" && $scope === "dernier") {
    $comboText = "Toutes les combinaisons – 1 140";
}
elseif ($type === "c3" && $scope === "tous") {
    $comboText = "Toutes les combinaisons – 54 740";
}


?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info</title>

<style>

/* =============================== */
/* === ПОЛНЫЙ STYLING из Q2 ====== */
/* =============================== */

/* 📦 Общий контейнер таблиц */
.tables-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  flex-wrap: nowrap;
}

/* 🔳 Обёртки таблиц */
.table-container,
.combo-table-container,
.number-stats-table {
  flex: 0 0 auto;
  width: max-content;
  max-height: 88vh;
  overflow: auto;
  margin-inline: auto;
  border-radius: 12px;
  border: 2px solid #a4a1a1;
}

.table-container {
  background: #00000005;
  box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.3);
}

/* 📊 Общий стиль таблиц */
.interactive-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 18px;
  font-family: 'Shadows Into Light', cursive;
  color: #000000;
  scrollbar-width: thin;
  scrollbar-color: rgb(30, 0, 255) transparent;
}

/* 📌 Закреплённые заголовки */
.interactive-table thead th {
  position: sticky;
  padding: 9px;
  top: 0;
  background-color: rgb(163, 216, 234);
  z-index: 10;
  border-bottom: 2px solid #888;
}

/* 📋 Ячейки */
.interactive-table td {
  padding: 9px;
  border-bottom: 1px dashed #777777;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 🖱️ Подсветка строк */
.interactive-table tr:hover {
  background-color: rgba(161, 161, 161, 0.493);
  transition: background-color 0.3s ease;
}

.interactive-table td:hover {
  background-color: rgba(202, 202, 202, 0.485);
}

/* 🔽 Сортировка */
.interactive-table th.sort-asc::after {
  content: " ▲";
  font-size: 0.8em;
}

.interactive-table th.sort-desc::after {
  content: " ▼";
  font-size: 0.8em;
}

/* 🔵 Кружки (шарики) */
.circle {
  display: inline-block;
  width: 28px;
  height: 28px;
  line-height: 28px;
  border-radius: 50%;
  background-color: #7eb0ea;
  color: #000000;
  font-weight: bold;
  text-align: center;
  box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
  font-family: Arial, sans-serif;
}

/* =============================== */
/* === Banco Info базовый CSS ==== */
/* =============================== */

.banco-info-page {
  font-family: sans-serif;
  margin: 0;
  padding: 0;
  text-align: center;
  background: transparent;
}

/* Меню выбора */
.menu-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin: 20px 0;
}

select {
  font-size: 14px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  background: rgba(255, 255, 255, 0.9);
  cursor: pointer;
}

/* Инфо-блок */
.info-placeholder {
    max-width: 850px;
    margin: 15px auto;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.12);
    border-left: 4px solid #007BFF;
    border-radius: 6px;
    font-size: 15px;
    text-align: center;
}

/* Первый блок — зелёная полоса */
.info-block-1 {
    border-left-color: #28a745;
}

/* Второй блок — оранжевая полоса */
.info-block-2 {
    border-left-color: #FF8C00;
}

</style>

</head>

<body class="banco-info-page">

  <!-- Меню -->
  <div class="menu-row">

    <select id="combinaisonSelect" onchange="updateParams()">
      <option value="c2" <?= ($type==='c2'?'selected':'') ?>>Combinaison de 2</option>
      <option value="c3" <?= ($type==='c3'?'selected':'') ?>>Combinaison de 3</option>
    </select>

    <select id="tirageSelect" onchange="updateParams()">
      <option value="dernier" <?= ($scope==='dernier'?'selected':'') ?>>Dernier tirage</option>
      <option value="tous" <?= ($scope==='tous'?'selected':'') ?>>Tous les tirages</option>
    </select>

  </div>

  <!-- Инфо -->
<div class="info-placeholder info-block-1">
    <p><b><?= implode(" ", $lastNums) ?></b></p>
</div>

<div class="info-placeholder info-block-2">
    <p><?= $comboText ?></p>
</div>

  <!-- Таблица -->
  <div class="tables-wrapper">
    <div class="table-container">
      <table class="interactive-table">
        <thead>
          <tr>
            <th>Tirage</th>

            <?php foreach ($fields as $f): ?>
              <th>#</th>
            <?php endforeach; ?>

            <th>Jours<br>passés</th>
            <th>L'avant<br>dernière</th>
            <th>Fois</th>
            <th>Max<br>jours passés</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>

            <td><?= htmlspecialchars($r['Tirage']) ?></td>

            <?php foreach ($fields as $f): ?>
              <td><span class="circle"><?= htmlspecialchars($r[$f]) ?></span></td>
            <?php endforeach; ?>

            <td><?= htmlspecialchars($r['days']) ?></td>
            <td><?= htmlspecialchars($r['days2']) ?></td>
            <td><?= htmlspecialchars($r['fois']) ?></td>
            <td><?= htmlspecialchars($r['max']) ?></td>

          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
function updateParams() {
    const type  = document.getElementById("combinaisonSelect").value;
    const scope = document.getElementById("tirageSelect").value;
    window.location.href = `?type=${type}&scope=${scope}`;
}
</script>

</body>
</html>