<?php
// === PHP логика ===
$resultMessage = '';
$resultColor = '';
$selectedNums = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numbers'])) {
    $numbers = array_map('intval', explode(',', $_POST['numbers']));
    if (count($numbers) !== 12) {
        $resultMessage = "Нужно выбрать ровно 12 чисел.";
        $resultColor = "red";
    } else {
        $conn = new mysqli("db", "user", "user", "toutourien");
        $conn->set_charset("utf8");

        if ($conn->connect_error) {
            $resultMessage = "Ошибка подключения к БД.";
            $resultColor = "red";
        } else {
            // Получаем все тиражи
            $sql = "SELECT n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout";
            $res = $conn->query($sql);

            $found = false;
            $needle = $numbers;
            sort($needle);

            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $tirage = array_map('intval', array_values($row));
                    sort($tirage);
                    if ($tirage === $needle) {
                        $found = true;
                        break;
                    }
                }
            }

            if ($found) {
                $resultMessage = "✅ Такая комбинация найдена!";
                $resultColor = "green";
            } else {
                $resultMessage = "❌ Такой комбинации нет.";
                $resultColor = "orange";
            }
        }
    }

    $selectedNums = $numbers; // сохранить выделенные
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Проверка Tout ou Rien</title>
  <style>
    body {
      font-family: sans-serif;
      text-align: center;
      padding: 1em;
    }

    .number-grid {
      display: grid;
      grid-template-columns: repeat(6, 50px);
      gap: 10px;
      justify-content: center;
      margin: 20px 0;
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
      transition: 0.2s;
    }

    .circle.selected {
      background-color: #007BFF;
      color: white;
      font-weight: bold;
    }

    button {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 1em;
      cursor: pointer;
    }

    #result {
      margin-top: 20px;
      font-size: 1.2em;
      font-weight: bold;
    }
  </style>
</head>
<body>

<h2>Проверка комбинации Tout ou Rien</h2>

<form method="POST" onsubmit="return prepareSubmit()">
  <input type="hidden" name="numbers" id="numbersInput">

  <div class="number-grid" id="grid">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle <?= in_array($i, $selectedNums) ? 'selected' : '' ?>" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <button type="submit">Проверить</button>
</form>

<?php if ($resultMessage): ?>
  <div id="result" style="color: <?= $resultColor ?>;"><?= $resultMessage ?></div>
<?php endif; ?>

<script>
  const selected = new Set(<?= json_encode($selectedNums) ?>);
  const grid = document.getElementById("grid");

  grid.querySelectorAll(".circle").forEach(circle => {
    const num = parseInt(circle.dataset.num);
    if (selected.has(num)) {
      circle.classList.add("selected");
    }

    circle.addEventListener("click", () => {
      if (selected.has(num)) {
        selected.delete(num);
        circle.classList.remove("selected");
      } else if (selected.size < 12) {
        selected.add(num);
        circle.classList.add("selected");
      }
    });
  });

  function prepareSubmit() {
    if (selected.size !== 12) {
      alert("Нужно выбрать ровно 12 чисел.");
      return false;
    }
    document.getElementById("numbersInput").value = Array.from(selected).join(',');
    return true;
  }
</script>

</body>
</html>