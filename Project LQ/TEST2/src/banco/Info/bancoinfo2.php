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
        if (!empty($rowLast["n$i"])) {
            $lastNums[] = (int)$rowLast["n$i"];
        }
    }
}

// для быстрого поиска: множество чисел последнего тиража
$lastNumsSet = array_flip($lastNums);

// === Статистика по comb2 (глобально по таблице) ===
$statsMinFois = null;
$statsMaxFois = null;
$statsMaxDays = null;
$statsMaxMax  = null;

$sqlStats = "SELECT 
    MIN(fois) AS minFois, 
    MAX(fois) AS maxFois, 
    MAX(days) AS maxDays,
    MAX(`max`) AS maxMax
FROM $table";

$resStats = $bancoConn->query($sqlStats);
if ($resStats && $resStats->num_rows > 0) {
    $rowStats     = $resStats->fetch_assoc();
    $statsMinFois = (int)$rowStats['minFois'];
    $statsMaxFois = (int)$rowStats['maxFois'];
    $statsMaxDays = (int)$rowStats['maxDays'];
    $statsMaxMax  = (int)$rowStats['maxMax'];
}

// === Текст для инфо-блока ===
$line1Text = "Toutes les combinaisons possibles 70/70 - 2 415";
$line2Text = "Combinaisons par tirage 20/70 - 190";

$line3Text = "Min/Max fois sorti- {$statsMinFois}/{$statsMaxFois}";
$line4Text = "Max Jours passés - {$statsMaxDays}";
$line5Text = "Max jours passés - {$statsMaxMax}";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info – Combinaisons de 2</title>
<link rel="stylesheet" href="bancoinfo.css">
</head>

<body class="banco-info-page">

<div class="banco-info-layout">
  <!-- ЛЕВАЯ КОЛОНКА: квадраты 1–70 + кнопки + меню + инфо-блок -->
  <div class="left-panel">

    <!-- Верхний инфо-блок: 1–70 + кнопки + меню -->
    <div class="info-placeholder info-block-1">
      <div class="numbers-grid">
        <?php for ($i = 1; $i <= 70; $i++): 
            $isLast = isset($lastNumsSet[$i]);
        ?>
          <span
            class="square-transparent filter-num <?= $isLast ? 'in-last' : '' ?>"
            data-num="<?= $i ?>"
          ><?= $i ?></span>
        <?php endfor; ?>
      </div>

      <div class="info-actions">
        <button id="executeFilter">Выполнить</button>
        <button id="resetFilter">Сброс</button>
      </div>

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
    </div>

    <!-- Нижний инфо-блок: 5 строк -->
    <div class="info-placeholder info-block-2">
      <p><?= htmlspecialchars($line1Text, ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars($line2Text, ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars($line3Text, ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars($line4Text, ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars($line5Text, ENT_QUOTES, 'UTF-8') ?></p>
    </div>

  </div>

  <!-- ПРАВАЯ КОЛОНКА: таблица -->
  <div class="right-panel">
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
  </div>
</div> <!-- /.banco-info-layout -->

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

// === Фильтрация по выбранным числам из верхнего инфо-блока ===
function initFilterUI() {
  const squares = Array.from(document.querySelectorAll(".filter-num"));
  const rows = Array.from(document.querySelectorAll(".interactive-table tbody tr"));
  const executeBtn = document.getElementById("executeFilter");
  const resetBtn   = document.getElementById("resetFilter");

  let selectedNums = []; // максимум 2 числа

  function updateSquaresVisual() {
    squares.forEach(span => {
      const num = parseInt(span.dataset.num, 10);
      span.classList.toggle("selected", selectedNums.includes(num));
    });
  }

  function applyFilter() {
    if (selectedNums.length === 0) {
      rows.forEach(row => row.style.display = "");
      return;
    }

    rows.forEach(row => {
      const numSpans = row.querySelectorAll(".circle");
      const nums = Array.from(numSpans).map(s => parseInt(s.textContent.trim(), 10));

      let show = false;

      if (selectedNums.length === 1) {
        show = nums.includes(selectedNums[0]);
      } else if (selectedNums.length === 2) {
        show = nums.includes(selectedNums[0]) && nums.includes(selectedNums[1]);
      }

      row.style.display = show ? "" : "none";
    });
  }

  function resetFilter() {
    selectedNums = [];
    updateSquaresVisual();
    rows.forEach(row => row.style.display = "");
  }

  squares.forEach(span => {
    span.addEventListener("click", () => {
      const num = parseInt(span.dataset.num, 10);
      if (selectedNums.includes(num)) {
        selectedNums = selectedNums.filter(n => n !== num);
      } else {
        if (selectedNums.length >= 2) {
          selectedNums.shift();
        }
        selectedNums.push(num);
      }
      updateSquaresVisual();
    });
  });

  if (executeBtn) {
    executeBtn.addEventListener("click", applyFilter);
  }

  if (resetBtn) {
    resetBtn.addEventListener("click", resetFilter);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  makeTablesSortable();
  initFilterUI();
});
</script>

</body>
</html>