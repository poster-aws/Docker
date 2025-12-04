<?php
require_once "../db.php"; // $bancoConn

// -------------------------------
// 1. Параметры меню
// -------------------------------
$type  = 'c3'; // фиксировано: comb3
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'dernier'; // 'dernier' | 'tous'

// -------------------------------
// 2. Таблица comb3
// -------------------------------
$table  = "comb3";
$fields = ['n1', 'n2', 'n3'];

// -------------------------------
// 2a. Выбранные числа для фильтра (только для scope = 'tous')
//     формат GET: ?scope=tous&sel=5,17,23
// -------------------------------
$selectedNums = [];
if ($scope === 'tous' && isset($_GET['sel']) && $_GET['sel'] !== '') {
    $parts = explode(',', $_GET['sel']);
    foreach ($parts as $p) {
        $n = (int)trim($p);
        if ($n >= 1 && $n <= 70) {
            $selectedNums[] = $n;
        }
    }
    $selectedNums = array_values(array_unique($selectedNums));
}

// -------------------------------
// 3–4. Загружаем данные
// -------------------------------
$rows = [];

if ($scope === 'dernier') {
    // Dernier tirage: вытаскиваем все комбинации последнего тиража (days = 0)
    $sql = "SELECT * FROM $table WHERE days = 0 ORDER BY Tirage DESC";
    $result = $bancoConn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) {
            $rows[] = $r;
        }
    }
} elseif ($scope === 'tous' && !empty($selectedNums)) {
    // Tous les tirages + есть выбранные числа → серверный фильтр
    if (count($selectedNums) === 1) {
        $a   = (int)$selectedNums[0];
        $sql = "SELECT * FROM $table
                WHERE n1 = $a OR n2 = $a OR n3 = $a
                ORDER BY Tirage DESC";
    } else {
        // берем все строки, где хотя бы одно из выбранных чисел встречается
        $inList = implode(',', array_map('intval', $selectedNums));
        $sql = "SELECT * FROM $table
                WHERE n1 IN ($inList)
                   OR n2 IN ($inList)
                   OR n3 IN ($inList)
                ORDER BY Tirage DESC";
    }

    $result = $bancoConn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) {
            $rowNums = [(int)$r['n1'], (int)$r['n2'], (int)$r['n3']];
            $ok = true;
            // оставляем только те строки, которые содержат ВСЕ выбранные числа
            foreach ($selectedNums as $sn) {
                if (!in_array($sn, $rowNums, true)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $rows[] = $r;
            }
        }
    }
}
// если scope='tous' и нет sel — $rows остаётся пустым, к comb3 вообще не обращаемся

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

// === Статистика по comb3 (глобально по таблице) ===
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
// C(70,3) = 54 740, C(20,3) = 1 140
$line1Text = "Toutes les combinaisons possibles 70/70 - 54 740";
$line2Text = "Combinaisons par tirage 20/70 - 1 140";

$line3Text = "Min/Max fois sorti- {$statsMinFois}/{$statsMaxFois}";
$line4Text = "Max Jours passés - {$statsMaxDays}";
$line5Text = "Max jours passés - {$statsMaxMax}";

// для сообщения "Zadayte filtr"
$showFilterMessage = ($scope === 'tous' && empty($selectedNums));

// передаём выбранные числа в JS для подсветки
$jsSelectedNums = json_encode($selectedNums, JSON_NUMERIC_CHECK);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info – Combinaisons de 3</title>
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
          <option value="c2">Combinaison de 2</option>
          <option value="c3" selected>Combinaison de 3</option>
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
        <p id="filterMessage" class="filter-message"<?= $showFilterMessage ? '' : ' style="display:none"' ?>>
          Zadayte filtr
        </p>

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
const scope          = "<?= $scope ?>";          // 'dernier' | 'tous'
const initialSelected = <?= $jsSelectedNums ?>;  // [..выбранные числа для scope=tous..] или []

function updateParams() {
    const comb  = document.getElementById("combinaisonSelect").value;
    const sc    = document.getElementById("tirageSelect").value;

    if (comb === "c2") {
        window.location.href = `bancoinfo2.php?scope=${sc}`;
    } else {
        window.location.href = `bancoinfo3.php?scope=${sc}`;
    }
}

// 🔁 Сортировка таблиц (как в bancoinfo2.php)
function makeTablesSortable() {
  const tables = document.querySelectorAll(".interactive-table");

  tables.forEach(table => {
    const headers = table.querySelectorAll("th");
    headers.forEach((th, columnIndex) => {
      th.style.cursor = "pointer";

      th.addEventListener("click", () => {
        const tbody = table.querySelector("tbody");
        const rows  = Array.from(tbody.querySelectorAll("tr"));
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

// === Фильтрация / запуск фильтра ===
function initFilterUI() {
  const squares    = Array.from(document.querySelectorAll(".filter-num"));
  const rows       = Array.from(document.querySelectorAll(".interactive-table tbody tr"));
  const executeBtn = document.getElementById("executeFilter");
  const resetBtn   = document.getElementById("resetFilter");
  const filterMsg  = document.getElementById("filterMessage");

  let selectedNums = Array.isArray(initialSelected) ? initialSelected.slice() : []; // максимум 3 числа

  function updateSquaresVisual() {
    squares.forEach(span => {
      const num = parseInt(span.dataset.num, 10);
      span.classList.toggle("selected", selectedNums.includes(num));
    });
  }

  // Клиентский фильтр — только для scope='dernier'
  function applyClientFilter() {
    if (selectedNums.length === 0) {
      rows.forEach(row => row.style.display = "");
      if (filterMsg && scope === 'tous') {
        filterMsg.style.display = rows.length === 0 ? "block" : "none";
      }
      return;
    }

    if (filterMsg) filterMsg.style.display = "none";

    rows.forEach(row => {
      const numSpans = row.querySelectorAll(".circle");
      const nums = Array.from(numSpans).map(s => parseInt(s.textContent.trim(), 10));

      let show = true;
      for (let sel of selectedNums) {
        if (!nums.includes(sel)) {
          show = false;
          break;
        }
      }

      row.style.display = show ? "" : "none";
    });
  }

  function onExecuteClick() {
    if (scope === 'tous') {
      // серверная фильтрация: редирект с параметром sel
      if (selectedNums.length === 0) {
        // без фильтра – просто оставляем пустую таблицу и надпись
        window.location.href = 'bancoinfo3.php?scope=tous';
        return;
      }
      const params = new URLSearchParams(window.location.search);
      params.set('scope', 'tous');
      params.set('sel', selectedNums.join(','));
      window.location.search = params.toString();
    } else {
      // scope='dernier' — фильтрация по уже загруженным строкам
      applyClientFilter();
    }
  }

  function onResetClick() {
    selectedNums = [];
    updateSquaresVisual();

    if (scope === 'tous') {
      // сброс фильтра — чистый режим "Zadayte filtr"
      window.location.href = 'bancoinfo3.php?scope=tous';
    } else {
      // Dernier tirage — показываем все строки
      rows.forEach(row => row.style.display = "");
      if (filterMsg) filterMsg.style.display = "none";
    }
  }

  squares.forEach(span => {
    span.addEventListener("click", () => {
      const num = parseInt(span.dataset.num, 10);
      if (selectedNums.includes(num)) {
        selectedNums = selectedNums.filter(n => n !== num);
      } else {
        if (selectedNums.length >= 3) {
          selectedNums.shift();
        }
        selectedNums.push(num);
      }
      updateSquaresVisual();
    });
  });

  if (executeBtn) executeBtn.addEventListener("click", onExecuteClick);
  if (resetBtn)   resetBtn.addEventListener("click", onResetClick);

  // восстановим подсветку выбранных при scope=tous&sel=...
  updateSquaresVisual();
}

document.addEventListener("DOMContentLoaded", () => {
  makeTablesSortable();
  initFilterUI();
});
</script>

</body>
</html>