<?php
require_once "quotidienne\db.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $n1 = $_POST['n1'];
    $n2 = $_POST['n2'];
    $n3 = $_POST['n3'];
    $n4 = $_POST['n4'];
    $n5 = $_POST['n5'];
    $n6 = $_POST['n6'];
    $n7 = $_POST['n7'];
    $n8 = $_POST['n8'];
    $n9 = $_POST['n9'];

    $inserted = false;

    // Q2
    $stmt = $conn->prepare("INSERT INTO Q2 (Tirage, n1, n2) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2)");
    $stmt->bind_param("sii", $date, $n1, $n2);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;
    $stmt->close();

    // Q3
    $stmt = $conn->prepare("INSERT INTO Q3 (Tirage, n1, n2, n3) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2), n3 = VALUES(n3)");
    $stmt->bind_param("siii", $date, $n3, $n4, $n5);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;
    $stmt->close();

    // Q4
    $stmt = $conn->prepare("INSERT INTO Q4 (Tirage, n1, n2, n3, n4) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2), n3 = VALUES(n3), n4 = VALUES(n4)");
    $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;
    $stmt->close();

    // Вызов процедур, если были изменения
    if ($inserted) {
        $procedures = [
            'fill_Q2_stats_order',
            'fill_Q2_stats_norder',
            'fill_Q2_combo_stats_order',
            'fill_Q3_stats_order',
            'fill_Q3_stats_norder',
            'fill_Q3_combo_stats_order',
            'fill_Q4_stats_order',
            'fill_Q4_stats_norder',
            'fill_Q4_combo_stats_order'
        ];
        foreach ($procedures as $proc) {
            $conn->query("CALL $proc()");
        }
        $msg = ['class' => 'success', 'text' => "✅ Данные успешно сохранены и обновлены."];
    } else {
        $msg = ['class' => 'error', 'text' => "⚠️ Данные не были изменены."];
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Форма добавления в БД</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body {
      height: 100vh;
      font-family: sans-serif;
      background: #f0f0f0;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .center-container {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
      padding: 20px 30px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      min-width: 320px;
    }
    .row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    label { font-weight: bold; width: 40px; }
    select, input[type="date"], button {
      padding: 6px 12px;
      font-size: 16px;
    }
    .date-row { align-self: center; }
    button {
      align-self: center;
      margin-top: 10px;
      cursor: pointer;
    }
    .error { border: 2px solid red; }
    .msg { text-align: center; font-weight: bold; margin-bottom: 1em; }
    .msg.success { color: green; }
    .msg.error { color: red; }
  </style>
</head>
<body>

  <form class="center-container" method="post">
    <?php if (!empty($msg)): ?>
      <div class="msg <?= $msg['class'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <div class="row date-row">
      <input type="date" name="date" required />
    </div>

    <div class="row">
      <label>Q2:</label>
      <select name="n1" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n2" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
    </div>

    <div class="row">
      <label>Q3:</label>
      <select name="n3" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n4" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n5" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
    </div>

    <div class="row">
      <label>Q4:</label>
      <select name="n6" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n7" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n8" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
      <select name="n9" required><option value="">0–9</option><?php for ($i = 0; $i <= 9; $i++) echo "<option value='$i'>$i</option>"; ?></select>
    </div>

    <button type="submit">Отправить</button>
  </form>

  <script>
    document.querySelector('form').addEventListener('submit', function (e) {
      let hasError = false;
      const selects = this.querySelectorAll('select');

      selects.forEach(select => {
        if (!select.value) {
          select.classList.add('error');
          hasError = true;
        } else {
          select.classList.remove('error');
        }
      });

      if (hasError) e.preventDefault();
    });
  </script>

</body>
</html>