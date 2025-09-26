<?php
// === PHP логика ===
error_reporting(E_ALL);
ini_set('display_errors', 1);

$resultMessage = '';
$resultColor = ''; // green | orange | red
$selectedNums = [];
$distributionRows = []; // данные для таблицы (k, cnt)
$comboForTable   = [];  // сейчас не показываем «Комбинация:», но оставим для совместимости

/** Валидация ровно 12 разных чисел 1..24 */
function validate_numbers(array $nums, &$errMsg) {
    if (count($nums) !== 12) { $errMsg = "Нужно выбрать ровно 12 чисел."; return false; }
    $uniq = array_unique($nums);
    if (count($uniq) !== 12) { $errMsg = "Все 12 чисел должны быть разными."; return false; }
    foreach ($uniq as $n) {
        if (!is_int($n) || $n < 1 || $n > 24) { $errMsg = "Допустимы только числа от 1 до 24."; return false; }
    }
    return true;
}

/** Предзаполнение из GET ?pre=1,2,... (не обязательно) */
if (!empty($_GET['pre'])) {
    $pre = array_map('intval', explode(',', $_GET['pre']));
    $pre = array_values(array_unique(array_filter($pre, fn($x) => $x >= 1 && $x <= 24)));
    // ограничимся максимумом 12 для корректной подсветки
    $selectedNums = array_slice($pre, 0, 12);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numbers'])) {
    $numbers = array_map('intval', explode(',', $_POST['numbers']));
    $selectedNums = $numbers; // сохраняем подсветку на перерисовку

    if (!validate_numbers($numbers, $resultMessage)) {
        $resultColor = "red";
    } else {
        $conn = @new mysqli("db", "user", "user", "toutourien");
        if ($conn->connect_error) {
            $resultMessage = "Ошибка подключения к БД.";
            $resultColor = "red";
        } else {
            $conn->set_charset("utf8");
            $safeList = implode(',', array_map('intval', $numbers));

            // Строгая проверка: все 12 полей должны входить в набор (сумма булевых IN == 12)
            $sqlExact = "
                SELECT 1
                FROM Tout
                WHERE ((n1  IN ($safeList)) + (n2  IN ($safeList)) + (n3  IN ($safeList)) + (n4  IN ($safeList)) +
                       (n5  IN ($safeList)) + (n6  IN ($safeList)) + (n7  IN ($safeList)) + (n8  IN ($safeList)) +
                       (n9  IN ($safeList)) + (n10 IN ($safeList)) + (n11 IN ($safeList)) + (n12 IN ($safeList))) = 12
                LIMIT 1
            ";

            $resExact = $conn->query($sqlExact);
            if ($resExact && $resExact->num_rows > 0) {
                $resultMessage = "✅ Найдена";
                $resultColor = "green";
            } else {
                $resultMessage = "❌ Нет";
                $resultColor = "orange";
            }

            // === РАСПРЕДЕЛЕНИЕ СОВПАДЕНИЙ (всё локально) ===
            $comboForTable = $numbers;

            $distributionSql = "
              SELECT
                b.k AS matches,
                COALESCE(a.draws_count, 0) AS cnt
              FROM
                ( SELECT 0 AS k UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
                  UNION ALL SELECT 4 UNION ALL SELECT 8 UNION ALL SELECT 9
                  UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
                ) b
              LEFT JOIN (
                SELECT matches, COUNT(*) AS draws_count
                FROM (
                  SELECT (
                    (n1  IN ($safeList)) +
                    (n2  IN ($safeList)) +
                    (n3  IN ($safeList)) +
                    (n4  IN ($safeList)) +
                    (n5  IN ($safeList)) +
                    (n6  IN ($safeList)) +
                    (n7  IN ($safeList)) +
                    (n8  IN ($safeList)) +
                    (n9  IN ($safeList)) +
                    (n10 IN ($safeList)) +
                    (n11 IN ($safeList)) +
                    (n12 IN ($safeList))
                  ) AS matches
                  FROM Tout
                ) mm
                GROUP BY matches
              ) a
              ON a.matches = b.k
              ORDER BY b.k
            ";

            if ($resDist = $conn->query($distributionSql)) {
                while ($row = $resDist->fetch_assoc()) {
                    $distributionRows[] = [
                        'matches' => (int)$row['matches'], // 0,1,2,3,4,8,9,10,11,12
                        'cnt'     => (int)$row['cnt'],
                    ];
                }
                $resDist->free();
            }

            $conn->close();
        }
    }
}

/** Выигрышная шкала для нужных корзин */
function prize_for_matches(int $k): ?string {
    $map = [
        0  => '250 000$',
        1  => '1 000$',
        2  => '25$',
        3  => '10$',
        4  => '2$',
        8  => '2$',
        9  => '10$',
        10 => '25$',
        11 => '1 000$',
        12 => '250 000$',
    ];
    return $map[$k] ?? null;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Проверка Tout ou Rien</title>
  <style>
    html { background: transparent; }
    body {
      font-family: sans-serif;
      text-align: center;
      padding: 1em;
      background: transparent; /* прозрачный фон внутри iframe */
      margin: 0;
    }
    /* h2 { margin: 0.2em 0 0.6em; } */

    .number-grid {
      display: grid;
      grid-template-columns: repeat(6, 50px);
      gap: 10px;
      justify-content: center;
      /* margin: 12px 0 10px; */
    }
    .circle {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      background-color: #eee;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 1.2em;
      cursor: pointer;
      user-select: none;
      transition: 0.15s;
    }
    .circle:hover { transform: translateY(-1px); }
    .circle.selected { background-color: #007BFF; color: white; font-weight: bold; }
    .circle.locked { opacity: 0.45; cursor: not-allowed; }

    .row {
      display: flex;
      gap: 12px;
      align-items: center;
      justify-content: center;
      margin-top: 6px;
      flex-wrap: wrap;
    }
    .muted { color: #667; font-size: 0.95em; }

    .btn {
      padding: 8px 16px;
      font-size: 1em;
      cursor: pointer;
      border: 1px solid #bdbdbd;
      border-radius: 10px;
      background: #f5f5f5;
      min-width: 150px;
    }
    .btn:hover { filter: brightness(0.98); }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn-success { background: #e8f6ee; border-color: #bfe6cf; }
    .btn-warning { background: #fff4e5; border-color: #ffd19b; }
    .btn-error   { background: #fdecec; border-color: #f5b5b5; }

    /* === Окно таблицы === */
    .dist-wrap {
      margin-top: 16px;
      display: flex;
      justify-content: center;
    }
    .dist-card {
      width: min(680px, 95%);
      border: 1px solid #d7d7d7;
      border-radius: 12px;
      padding: 10px 14px 14px;
      background: #fafafa;
      box-shadow: 0 2px 6px rgba(0,0,0,0.06);
      text-align: left;
    }
    .counter-line {
      text-align: center;
      margin-bottom: 8px;
      color: #667;
      font-size: 0.95em;
    }
    table.dist {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.95em;
      background: white;
      border: 1px solid #e2e2e2;
      border-radius: 8px;
      overflow: hidden;
    }
    table.dist td {
      padding: 8px 10px;
      border-bottom: 1px solid #f0f0f0;
    }
    table.dist tr:last-child td { border-bottom: none; }
    table.dist td:nth-child(1) { width: 25%; text-align: center; font-weight: 600; }
    table.dist td:nth-child(2) { width: 40%; text-align: right; }
    table.dist td:nth-child(3) { width: 35%; text-align: right; font-weight: 600; }
    .note-empty { display:none; } /* по ТЗ никаких надписей в пустом состоянии */
  </style>
</head>
<body>

<form method="POST" onsubmit="return prepareSubmit()">
  <input type="hidden" name="numbers" id="numbersInput">

  <div class="number-grid" id="grid">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle <?= in_array($i, $selectedNums, true) ? 'selected' : '' ?>" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <div class="row">
    <?php
      $btnLabel    = $resultMessage ? $resultMessage : 'Проверить';
      $btnDisabled = $resultMessage ? 'disabled' : '';
      $btnClass    = '';
      if ($resultColor === 'green')      $btnClass = 'btn-success';
      elseif ($resultColor === 'orange') $btnClass = 'btn-warning';
      elseif ($resultColor === 'red')    $btnClass = 'btn-error';
    ?>
    <button id="checkBtn" type="submit" class="btn <?= $btnClass ?>" <?= $btnDisabled ?>>
      <?= htmlspecialchars($btnLabel, ENT_QUOTES) ?>
    </button>

    <button type="button" class="btn" id="resetBtn" onclick="resetSelection()">Сбросить</button>
  </div>
</form>

<!-- === Окно таблицы (без заголовков/надписей) === -->
<div class="dist-wrap">
  <div class="dist-card">
    <div id="counter" class="counter-line">Выбрано: <?= count(array_unique($selectedNums)) ?>/12</div>

    <div id="distTableContainer">
    <?php if (!empty($distributionRows)): ?>
      <table class="dist">
        <tbody>
          <?php foreach ($distributionRows as $r): ?>
            <tr>
              <td><?= (int)$r['matches'] ?>/12</td>
              <td><?= (int)$r['cnt'] ?></td>
              <td>
                <?php
                  $pr = prize_for_matches((int)$r['matches']);
                  echo $pr !== null ? htmlspecialchars($pr, ENT_QUOTES) : '';
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    </div>
  </div>
</div>

<script>
  const preselected = <?= json_encode(array_values(array_unique($selectedNums))) ?>;
  const selected = new Set(preselected);

// Fallback: прочитать ?pre= из URL внутри iframe, если сервер не заполнил preselected
(function applyPreFromQueryIfEmpty(){
  if (selected.size > 0) return;
  try {
    const usp = new URLSearchParams(window.location.search);
    const pre = (usp.get('pre') || '').split(',').map(s => parseInt(s, 10)).filter(n => Number.isInteger(n) && n >= 1 && n <= 24);
    const uniq = Array.from(new Set(pre)).slice(0, 12);
    if (uniq.length) { uniq.forEach(n => selected.add(n)); }
  } catch(e) {}
})();
  
  const grid = document.getElementById("grid");
  const counterEl = document.getElementById("counter");
  const checkBtn = document.getElementById("checkBtn");

  function updateCounter() {
    if (counterEl) counterEl.textContent = `Выбрано: ${selected.size}/12`;
    const lock = selected.size >= 12;
    grid.querySelectorAll(".circle").forEach(c => {
      if (!c.classList.contains("selected")) {
        c.classList.toggle("locked", lock);
      } else {
        c.classList.remove("locked");
      }
    });
  }

  grid.querySelectorAll(".circle").forEach(circle => {
    const num = parseInt(circle.dataset.num, 10);
    if (selected.has(num)) circle.classList.add("selected");

    circle.addEventListener("click", () => {
      if (selected.has(num)) {
        selected.delete(num);
        circle.classList.remove("selected");
      } else {
        if (selected.size >= 12) return;
        selected.add(num);
        circle.classList.add("selected");
      }
      updateCounter();
    });
  });

function resetSelection() {
  selected.clear();
  grid.querySelectorAll(".circle").forEach(c => c.classList.remove("selected", "locked"));
  updateCounter();

  // Полный сброс состояния кнопки
  checkBtn.disabled = false;
  checkBtn.textContent = 'Проверить';
  checkBtn.classList.remove('btn-success','btn-warning','btn-error');

  // Очистка таблицы результатов
  const distContainer = document.getElementById("distTableContainer");
  if (distContainer) distContainer.innerHTML = "";
}

  function prepareSubmit() {
    if (checkBtn.disabled) return false; // не отправляем повторно прежний результат
    if (selected.size !== 12) {
      alert("Нужно выбрать ровно 12 разных чисел (1–24).");
      return false;
    }
    const arr = Array.from(selected);
    document.getElementById("numbersInput").value = arr.join(',');
    return true;
  }

  // Клавиатурные шорткаты: Esc — сброс; Ctrl/⌘+Enter — проверка
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      resetSelection();
    } else if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      if (!checkBtn.disabled) {
        if (prepareSubmit()) { checkBtn.form.submit(); }
      }
    }
  });

  // первичная отрисовка
  updateCounter();
</script>

</body>
</html>