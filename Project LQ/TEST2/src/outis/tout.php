<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$toutMsg = null;
$qMsg = null;

// === Подключения к БД ===
$conn_tout = new mysqli("db", "user", "user", "toutourien");
$conn_tout->set_charset("utf8");
if ($conn_tout->connect_error) die("Connection failed (toutourien): " . $conn_tout->connect_error);

$conn_q = new mysqli("db", "user", "user", "quotidienne2");
$conn_q->set_charset("utf8");
if ($conn_q->connect_error) die("Connection failed (quotidienne2): " . $conn_q->connect_error);

// === Обработка формы Q2/Q3/Q4 ===
if (isset($_POST['q-submit'])) {
    $date = $_POST['date'] ?? null;
    $n1 = $_POST['n1'];
    $n2 = $_POST['n2'];
    $n3 = $_POST['n3'];
    $n4 = $_POST['n4'];
    $n5 = $_POST['n5'];
    $n6 = $_POST['n6'];
    $n7 = $_POST['n7'];
    $n8 = $_POST['n8'];
    $n9 = $_POST['n9'];

    $q2_exists = $conn_q->query("SELECT 1 FROM Q2 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q3_exists = $conn_q->query("SELECT 1 FROM Q3 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q4_exists = $conn_q->query("SELECT 1 FROM Q4 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

    if ($q2_exists && $q3_exists && $q4_exists) {
        $qMsg = ['class' => 'error', 'text' => "⚠️ Данные не были изменены."];
    } else {
        $stmt = $conn_q->prepare("REPLACE INTO Q2 (Tirage, n1, n2) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $date, $n1, $n2);
        $stmt->execute(); $stmt->close();

        $stmt = $conn_q->prepare("REPLACE INTO Q3 (Tirage, n1, n2, n3) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siii", $date, $n3, $n4, $n5);
        $stmt->execute(); $stmt->close();

        $stmt = $conn_q->prepare("REPLACE INTO Q4 (Tirage, n1, n2, n3, n4) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9);
        $stmt->execute(); $stmt->close();

        $procedures = [
            'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order',
            'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order',
            'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order'
        ];
        foreach ($procedures as $proc) $conn_q->query("CALL $proc()");
        $qMsg = ['class' => 'success', 'text' => "✅ Данные успешно сохранены и обновлены."];
    }
}

// === Обработка формы Tout ===
if (isset($_POST['tout-submit'])) {
    $date = $_POST['tout_date'] ?? null;
    $selected = explode(',', $_POST['tout_selected'] ?? '');
    $numbers = array_map('intval', $selected);
    if (count($numbers) !== 12) {
        $toutMsg = ['class' => 'error', 'text' => '❌ Нужно выбрать ровно 12 чисел.'];
    } else {
        $exists = $conn_tout->query("SELECT 1 FROM Tout WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

        if ($exists) {
            $toutMsg = ['class' => 'error', 'text' => "⚠️ Запись на эту дату уже существует."];
        } else {
            $stmt = $conn_tout->prepare("INSERT INTO Tout (Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("s" . str_repeat("i", 12), $date, ...$numbers);
            $stmt->execute();
            $stmt->close();

            $toutMsg = ['class' => 'success', 'text' => "✅ Запись успешно добавлена."];
        }
    }
}

$conn_tout->close();
$conn_q->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Добавление тиражей</title>
  <style>
    body {
      font-family: sans-serif;
      background: #eef;
      padding: 30px;
      display: flex;
      flex-direction: row;
      gap: 60px;
      justify-content: center;
      align-items: flex-start;
    }
    form {
      background: #fff;
      padding: 20px 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px #999;
      display: flex;
      flex-direction: column;
      gap: 12px;
      width: 360px;
    }
    .numbers {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
    }
    .circles {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
      margin: 10px 0;
    }
    .circle {
      width: 40px;
      height: 40px;
      background: #ddd;
      color: black;
      font-weight: bold;
      border-radius: 50%;
      text-align: center;
      line-height: 40px;
      cursor: pointer;
      transition: 0.2s;
      user-select: none;
    }
    .circle.selected {
      background: #2e8b57;
      color: white;
    }
    input[type="date"], input[type="number"], select {
      padding: 6px;
      font-size: 16px;
      width: 100%;
    }
    .msg {
      text-align: center;
      font-weight: bold;
    }
    .msg.success { color: green; }
    .msg.error { color: red; }
    button {
      padding: 10px;
      font-size: 16px;
      background: #4444ee;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
  </style>
</head>
<body>

<!-- Форма Q2/Q3/Q4 -->
<form method="post">
  <h3>Добавить Q2 / Q3 / Q4</h3>
  <?php if ($qMsg): ?><div class="msg <?= $qMsg['class'] ?>"><?= $qMsg['text'] ?></div><?php endif; ?>
  <input type="date" name="date" required />
  <div class="numbers">
    <?php for ($i = 0; $i <= 9; $i++) $options .= "<option value='$i'>$i</option>"; ?>
    <select name="n1"><?= $options ?></select>
    <select name="n2"><?= $options ?></select>
    <select name="n3"><?= $options ?></select>
    <select name="n4"><?= $options ?></select>
    <select name="n5"><?= $options ?></select>
    <select name="n6"><?= $options ?></select>
    <select name="n7"><?= $options ?></select>
    <select name="n8"><?= $options ?></select>
    <select name="n9"><?= $options ?></select>
  </div>
  <button type="submit" name="q-submit">Сохранить Q</button>
</form>

<!-- Форма Tout -->
<form method="post">
  <h3>Добавить Tout</h3>
  <?php if ($toutMsg): ?><div class="msg <?= $toutMsg['class'] ?>"><?= $toutMsg['text'] ?></div><?php endif; ?>
  <input type="date" name="tout_date" required />
  <div class="circles">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>
  <input type="hidden" name="tout_selected" id="tout_selected" required />
  <button type="submit" name="tout-submit">Сохранить Tout</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const circles = document.querySelectorAll('.circle');
  const hiddenInput = document.getElementById('tout_selected');

  circles.forEach(circle => {
    circle.addEventListener('click', () => {
      if (circle.classList.contains('selected')) {
        circle.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.circle.selected');
        if (selected.length >= 12) return;
        circle.classList.add('selected');
      }

      const selectedValues = Array.from(document.querySelectorAll('.circle.selected'))
        .map(el => el.dataset.num);
      hiddenInput.value = selectedValues.join(',');
    });
  });
});
</script>

</body>
</html>
