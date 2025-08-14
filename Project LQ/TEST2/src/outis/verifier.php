<?php
// === PHP логика ===
error_reporting(E_ALL);
ini_set('display_errors', 1);

$resultMessage = '';
$resultColor = ''; // green | orange | red
$selectedNums = [];

function validate_numbers(array $nums, &$errMsg) {
    if (count($nums) !== 12) { $errMsg = "Нужно выбрать ровно 12 чисел."; return false; }
    $uniq = array_unique($nums);
    if (count($uniq) !== 12) { $errMsg = "Все 12 чисел должны быть разными."; return false; }
    foreach ($uniq as $n) {
        if (!is_int($n) || $n < 1 || $n > 24) { $errMsg = "Допустимы только числа от 1 до 24."; return false; }
    }
    return true;
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
            // Проверка множественного совпадения (каждая колонка в выборке)
            $sql = "
                SELECT 1
                FROM Tout
                WHERE n1 IN ($safeList) AND n2 IN ($safeList) AND n3 IN ($safeList) AND n4 IN ($safeList)
                  AND n5 IN ($safeList) AND n6 IN ($safeList) AND n7 IN ($safeList) AND n8 IN ($safeList)
                  AND n9 IN ($safeList) AND n10 IN ($safeList) AND n11 IN ($safeList) AND n12 IN ($safeList)
                LIMIT 1
            ";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $resultMessage = "✅ Найдена";
                $resultColor = "green";
            } else {
                $resultMessage = "❌ Нет";
                $resultColor = "orange";
            }
            $conn->close();
        }
    }
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
    }

    h2 { margin: 0.2em 0 0.6em; }

    .number-grid {
      display: grid;
      grid-template-columns: repeat(6, 50px);
      gap: 10px;
      justify-content: center;
      margin: 12px 0 10px;
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
    .circle.selected {
      background-color: #007BFF;
      color: white;
      font-weight: bold;
    }
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
    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
    /* Цвета результата прямо на кнопке */
    .btn-success { background: #e8f6ee; border-color: #bfe6cf; }
    .btn-warning { background: #fff4e5; border-color: #ffd19b; }
    .btn-error   { background: #fdecec; border-color: #f5b5b5; }

    /* убрали блок результата под формой */
  </style>
</head>
<body>

<!-- <h2>Проверка комбинации Tout ou Rien</h2> -->

<form method="POST" onsubmit="return prepareSubmit()">
  <input type="hidden" name="numbers" id="numbersInput">

  <div class="number-grid" id="grid">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle <?= in_array($i, $selectedNums, true) ? 'selected' : '' ?>" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <div class="row">
    <div id="counter" class="muted">Выбрано: <?= count(array_unique($selectedNums)) ?>/12</div>
    <!-- <div class="muted">Допустимы только 12 разных чисел 1–24</div> -->
  </div>

  <div class="row">
    <?php
      // Настроим параметры кнопки «Проверить» в зависимости от ответа
      $btnLabel   = $resultMessage ? $resultMessage : 'Проверить';
      $btnDisabled= $resultMessage ? 'disabled' : '';
      $btnClass   = '';
      if ($resultColor === 'green')  $btnClass = 'btn-success';
      elseif ($resultColor === 'orange') $btnClass = 'btn-warning';
      elseif ($resultColor === 'red')    $btnClass = 'btn-error';
    ?>
    <button id="checkBtn" type="submit" class="btn <?= $btnClass ?>" <?= $btnDisabled ?>>
      <?= htmlspecialchars($btnLabel, ENT_QUOTES) ?>
    </button>

    <button type="button" class="btn" onclick="resetSelection()">Сбросить</button>
  </div>
</form>

<script>
  const preselected = <?= json_encode(array_values(array_unique($selectedNums))) ?>;
  const selected = new Set(preselected);
  const grid = document.getElementById("grid");
  const counterEl = document.getElementById("counter");
  const checkBtn = document.getElementById("checkBtn");

  function updateCounter() {
    counterEl.textContent = `Выбрано: ${selected.size}/12`;
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
    // Полный сброс: выбор, счётчик, и кнопка «Проверить» возвращается в исходное состояние
    selected.clear();
    grid.querySelectorAll(".circle").forEach(c => c.classList.remove("selected"));
    updateCounter();

    checkBtn.disabled = false;
    checkBtn.textContent = 'Проверить';
    checkBtn.classList.remove('btn-success','btn-warning','btn-error');
  }

  function prepareSubmit() {
    // Если кнопка уже «заблокирована» (после результата), не отправляем повторно
    if (checkBtn.disabled) return false;

    if (selected.size !== 12) {
      alert("Нужно выбрать ровно 12 разных чисел (1–24).");
      return false;
    }
    const arr = Array.from(selected);
    document.getElementById("numbersInput").value = arr.join(',');
    return true; // отправляем форму — после ответа сервер отрисует результат прямо в кнопке и заблокирует её
  }

  // первичная отрисовка
  updateCounter();
</script>

</body>
</html>