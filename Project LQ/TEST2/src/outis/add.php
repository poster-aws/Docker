<!-- src/outis/add.php -->

<?php
require_once "../quotidienne/db.php";  // $conn для Q234
require_once "../toutourien/db.php";   // $toutConn для Tout
require_once "../banco/db.php";         // $bancoConn для Banco
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = null;
$toutMsg = null;
$bancoMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (isset($_POST['submit_q'])) {
    $date = $_POST['date_q'];
    $n1 = $_POST['n1'];
    $n2 = $_POST['n2'];
    $n3 = $_POST['n3'];
    $n4 = $_POST['n4'];
    $n5 = $_POST['n5'];
    $n6 = $_POST['n6'];
    $n7 = $_POST['n7'];
    $n8 = $_POST['n8'];
    $n9 = $_POST['n9'];

    $q2_exists = $conn->query("SELECT 1 FROM Q2 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q3_exists = $conn->query("SELECT 1 FROM Q3 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q4_exists = $conn->query("SELECT 1 FROM Q4 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

    if ($q2_exists && $q3_exists && $q4_exists) {
      $msg = ['class' => 'error', 'text' => "⚠️ Q2/Q3/Q4: дата уже существует."];
    } else {
      $stmt = $conn->prepare("REPLACE INTO Q2 (Tirage, n1, n2) VALUES (?, ?, ?)");
      if ($stmt) { $stmt->bind_param("sii", $date, $n1, $n2); $stmt->execute(); $stmt->close(); }

      $stmt = $conn->prepare("REPLACE INTO Q3 (Tirage, n1, n2, n3) VALUES (?, ?, ?, ?)");
      if ($stmt) { $stmt->bind_param("siii", $date, $n3, $n4, $n5); $stmt->execute(); $stmt->close(); }

      $stmt = $conn->prepare("REPLACE INTO Q4 (Tirage, n1, n2, n3, n4) VALUES (?, ?, ?, ?, ?)");
      if ($stmt) { $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9); $stmt->execute(); $stmt->close(); }

      $procedures = [
        'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order',
        'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order',
        'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order'
      ];
      foreach ($procedures as $proc) $conn->query("CALL $proc()");

      $msg = ['class' => 'success', 'text' => "✅ Q2/Q3/Q4: данные добавлены."];
    }
  }

  if (isset($_POST['tout-submit'])) {
    $date = $_POST['tout_date'] ?? null;
    $selected = explode(',', $_POST['tout_selected'] ?? '');
    $numbers = array_map('intval', $selected);
    if (count($numbers) !== 12) {
      $toutMsg = ['class' => 'error', 'text' => '❌ Нужно выбрать ровно 12 чисел.'];
    } else {
      $exists = $toutConn->query("SELECT 1 FROM Tout WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
      if ($exists) {
        $toutMsg = ['class' => 'error', 'text' => "⚠️ Запись на эту дату уже существует."];
      } else {
        $stmt = $toutConn->prepare("REPLACE INTO Tout (Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
          $stmt->bind_param("siiiiiiiiiiii",
              $date,
              $numbers[0], $numbers[1], $numbers[2], $numbers[3], $numbers[4], $numbers[5],
              $numbers[6], $numbers[7], $numbers[8], $numbers[9], $numbers[10], $numbers[11]
          );
          $stmt->execute(); $stmt->close();
          $toutMsg = ['class' => 'success', 'text' => "✅ Tout: данные добавлены."];
        }
      }
    }
  }

  if (isset($_POST['banco-submit'])) {
    $date = $_POST['banco_date'] ?? null;
    $turbo = intval($_POST['banco_turbo']);
    $selected = explode(',', $_POST['banco_selected'] ?? '');
    $numbers = array_map('intval', $selected);
    if (count($numbers) !== 20) {
      $bancoMsg = ['class' => 'error', 'text' => '❌ Нужно выбрать ровно 20 чисел для Banco.'];
    } else {
      $exists = $bancoConn->query("SELECT 1 FROM banco WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
      if ($exists) {
        $bancoMsg = ['class' => 'error', 'text' => "⚠️ Запись на эту дату уже существует в Banco."];
      } else {
        $placeholders = implode(',', array_fill(0, 20, '?'));
        $sql = "REPLACE INTO banco (Tirage, " . implode(',', array_map(fn($n) => "n$n", range(1,20))) . ", turbo)
                VALUES (?, $placeholders, ?)";
        $stmt = $bancoConn->prepare($sql);
        if ($stmt) {
          $types = "s" . str_repeat("i", 21); // 1 string (date) + 21 ints (20 numbers + turbo)
          $params = array_merge([$date], $numbers, [$turbo]);
          // bind_param requires references
          $refs = [];
          foreach ($params as $k => $v) {
            $refs[$k] = &$params[$k];
          }
          array_unshift($refs, $types);
          call_user_func_array([$stmt, 'bind_param'], $refs);

          $stmt->execute();
          $stmt->close();
          $bancoMsg = ['class' => 'success', 'text' => "✅ Banco: данные добавлены."];
        }
      }
    }
  }

  $conn->close();
  $toutConn->close();
  $bancoConn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Добавление тиражей</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body {
      font-family: sans-serif;
      background: #e0e0e0;
      padding: 20px;
    }
    .form-block {
      background: #fff;
      border-radius: 8px;
      padding: 20px 30px;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      margin-bottom: 30px;
      max-width: 400px;
    }
    .form-block h2 {
      margin-bottom: 15px;
    }
    .row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    select, input[type="date"], button {
      padding: 6px 12px;
      font-size: 16px;
    }
    button {
      display: block;
      margin: 10px auto 0;
    }
    .msg {
      padding: 10px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
    .circles {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
      margin: 10px 0;
    }
    .circle {
      width: 40px;
      height: 40px;
      background: #ccc;
      color: black;
      border-radius: 50%;
      text-align: center;
      line-height: 40px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.2s;
    }
    .circle.selected {
      background: #2e8b57;
      color: white;
    }
  </style>
</head>
<body>

<?php if (!empty($msg)): ?>
  <div class="msg <?= $msg['class'] ?>"><?= $msg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($toutMsg)): ?>
  <div class="msg <?= $toutMsg['class'] ?>"><?= $toutMsg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($bancoMsg)): ?>
  <div class="msg <?= $bancoMsg['class'] ?>"><?= $bancoMsg['text'] ?></div>
<?php endif; ?>

<!-- Q2/Q3/Q4 -->
<form class="form-block" method="post">
  <h2>Добавить Q2 / Q3 / Q4</h2>
  <div class="row">
    <label>Дата:</label>
    <input type="date" name="date_q" required />
  </div>
  <div class="row">
    <label>Q2:</label>
    <select name="n1"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n2"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
  </div>
  <div class="row">
    <label>Q3:</label>
    <select name="n3"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n4"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n5"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
  </div>
  <div class="row">
    <label>Q4:</label>
    <select name="n6"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n7"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n8"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
    <select name="n9"><?php for ($i = 0; $i <= 9; $i++) echo "<option>$i</option>"; ?></select>
  </div>
  <button type="submit" name="submit_q">Сохранить</button>
</form>

<!-- Tout -->
<form class="form-block" method="post">
  <h2>Добавить Tout ou Rien</h2>
  <div class="row">
    <label>Дата:</label>
    <input type="date" name="tout_date" required />
  </div>
  <div class="circles">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>
  <input type="hidden" name="tout_selected" id="tout_selected" required />
  <button type="submit" name="tout-submit">Сохранить Tout</button>
</form>

<!-- Banco -->
<form class="form-block banco-form" method="post">
  <h2>Добавить Banco</h2>
  <div class="row">
    <label>Дата:</label>
    <input type="date" name="banco_date" required />
  </div>
  <div class="circles banco-circles">
    <?php for ($i = 1; $i <= 70; $i++): ?>
      <div class="circle" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>
  <div class="row">
    <label>Turbo:</label>
    <select name="banco_turbo">
      <?php for ($i = 1; $i <= 10; $i++) echo "<option>$i</option>"; ?>
    </select>
  </div>
  <input type="hidden" name="banco_selected" id="banco_selected" required />
  <button type="submit" name="banco-submit">Сохранить Banco</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  /** === Tout logic === */
  const circles = document.querySelectorAll('.circle:not(.banco-circles .circle)');
  const hiddenInput = document.getElementById('tout_selected');

  circles.forEach(circle => {
    circle.addEventListener('click', () => {
      if (circle.classList.contains('selected')) {
        circle.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.circles .circle.selected');
        if (selected.length >= 12) return;
        circle.classList.add('selected');
      }

      const selectedValues = Array.from(document.querySelectorAll('.circles .circle.selected'))
        .map(el => el.dataset.num);
      hiddenInput.value = selectedValues.join(',');
    });
  });

  /** === Banco logic === */
  const bancoCircles = document.querySelectorAll('.banco-circles .circle');
  const bancoHiddenInput = document.getElementById('banco_selected');

  bancoCircles.forEach(circle => {
    circle.addEventListener('click', () => {
      if (circle.classList.contains('selected')) {
        circle.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.banco-circles .circle.selected');
        if (selected.length >= 20) return;
        circle.classList.add('selected');
      }

      const selectedValues = Array.from(document.querySelectorAll('.banco-circles .circle.selected'))
        .map(el => el.dataset.num);
      bancoHiddenInput.value = selectedValues.join(',');
    });
  });
});
</script>

</body>
</html>