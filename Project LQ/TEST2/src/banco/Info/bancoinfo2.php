<?php
require_once "../db.php"; // $bancoConn

// -------------------------------
// 1. Параметры меню
// -------------------------------
$type  = 'c2';                                     // фиксировано: comb2
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'dernier'; // dernier | tous

// -------------------------------
// 2. Таблица comb2
// -------------------------------
$table  = "comb2";
$fields = ['n1', 'n2'];

// -------------------------------
// 3. SQL по scope
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

// === Последний тираж Banco (20 чисел) ===
$lastNums = [];
$sqlLast = "SELECT * FROM banco ORDER BY Tirage DESC LIMIT 1";
$resLast = $bancoConn->query($sqlLast);
if ($resLast && $resLast->num_rows > 0) {
    $rowLast = $resLast->fetch_assoc();
    for ($i = 1; $i <= 20; $i++) {
        $lastNums[] = $rowLast["n$i"];
    }
}

// === Текст строки №2 ===
$comboText = "";
if ($scope === "dernier") {
    $comboText = "Toutes les combinaisons – 190";
} else {
    $comboText = "Toutes les combinaisons – 2 415";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info – Combinaisons de 2</title>
<link rel="stylesheet" href="bancoinfo.css">
</head>

<body class="banco-info-page">

  <!-- Инфо-блоки -->
  <div class="info-placeholder info-block-1">
    <p>
      <?php foreach ($lastNums as $num): ?>
        <span class="square-transparent"><?= $num ?></span>
      <?php endforeach; ?>
    </p>
  </div>

  <div class="info-placeholder info-block-2">
    <p><?= $comboText ?></p>
  </div>

  <!-- Меню -->
  <div class="menu-row">

    <select id="combinaisonSelect" onchange="updateParams()">
      <option value="c2" selected>Combinaison de 2</option>
      <option value="c3">Combinaison de 3</option>
    </select>

    <select id="tirageSelect" onchange="updateParams()">
      <option value="dernier" <?= ($scope==='dernier'?'selected':'') ?>>Dernier tirage</option>
      <option value="tous" <?= ($scope==='tous'?'selected':'') ?>>Tous les tirages</option>
    </select>

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
    const comb  = document.getElementById("combinaisonSelect").value;
    const scope = document.getElementById("tirageSelect").value;

    if (comb === "c2") {
        window.location.href = `bancoinfo2.php?scope=${scope}`;
    } else {
        window.location.href = `bancoinfo3.php?scope=${scope}`;
    }
}

// 🔁 Сортировка таблиц
function makeTablesSortable() {
  const tables = document.querySelectorAll(".interactive-table");

  tables.forEach(table => {
    const headers = table.querySelectorAll("th");
    headers.forEach((th, columnIndex) => {
      th.style.cursor = "pointer";

      th.addEventListener("click", () => {
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        const isAscending = th.classList.contains("sort-asc");

        headers.forEach(h => h.classList.remove("sort-asc", "sort-desc"));

        const sortedRows = rows.sort((a, b) => {
          const aText = a.children[columnIndex].innerText.trim();
          const bText = b.children[columnIndex].innerText.trim();

          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);

          if (aVal < bVal) return isAscending ? 1 : -1;
          if (aVal > bVal) return isAscending ? -1 : 1;
          return 0;
        });

        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));

        th.classList.toggle("sort-asc", !isAscending);
        th.classList.toggle("sort-desc", isAscending);
      });
    });
  });
}

document.addEventListener("DOMContentLoaded", makeTablesSortable);
</script>

</body>
</html>