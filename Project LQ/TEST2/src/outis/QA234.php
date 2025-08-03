<?php
require_once "../quotidienne/db.php";  // подключает $conn для Q2/Q3/Q4
require_once "../toutourien/db.php";  // подключает $toutConn для Tout
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (isset($_POST['submit_q'])) {
    // === Обработка формы Q2/Q3/Q4 ===
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
      $stmt->bind_param("sii", $date, $n1, $n2);
      $stmt->execute(); $stmt->close();

      $stmt = $conn->prepare("REPLACE INTO Q3 (Tirage, n1, n2, n3) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("siii", $date, $n3, $n4, $n5);
      $stmt->execute(); $stmt->close();

      $stmt = $conn->prepare("REPLACE INTO Q4 (Tirage, n1, n2, n3, n4) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9);
      $stmt->execute(); $stmt->close();

      $procedures = [
        'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order',
        'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order',
        'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order'
      ];
      foreach ($procedures as $proc) $conn->query("CALL $proc()");

      $msg = ['class' => 'success', 'text' => "✅ Q2/Q3/Q4: данные добавлены."];
    }
  }

  if (isset($_POST['submit_tout'])) {
    // === Обработка формы TOUT ===
    $date = $_POST['date_tout'];
    $tn = [];
    for ($i = 1; $i <= 12; $i++) {
      $tn[$i] = isset($_POST["t$i"]) ? (int)$_POST["t$i"] : null;
    }

    $tout_exists = $toutConn->query("SELECT 1 FROM Tout WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

    if ($tout_exists) {
      $msg = ['class' => 'error', 'text' => "⚠️ TOUT: дата уже существует."];
    } else {
      $stmt = $toutConn->prepare("REPLACE INTO Tout (Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("siiiiiiiiiiii",
          $date,
          $tn[1], $tn[2], $tn[3], $tn[4], $tn[5], $tn[6],
          $tn[7], $tn[8], $tn[9], $tn[10], $tn[11], $tn[12]
      );
      $stmt->execute(); $stmt->close();

      $msg = ['class' => 'success', 'text' => "✅ TOUT: данные добавлены."];
    }
  }

  $conn->close();
  $toutConn->close();
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
    .form-block button {
      display: block;
      margin: 0 auto;
      margin-top: 10px;
    }
    .tout-row input[type=number] {
      -moz-appearance: textfield;
    }
    .tout-row input::-webkit-outer-spin-button,
    .tout-row input::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    .row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    label {
      font-weight: bold;
      min-width: 50px;
    }
    select, input[type="date"], input[type="number"], button {
      padding: 6px 12px;
      font-size: 16px;
    }
    input[type="number"] {
      width: 50px;
    }
    .msg {
      padding: 10px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
  </style>
</head>
<body>

<?php if (!empty($msg)): ?>
  <div class="msg <?= $msg['class'] ?>"><?= $msg['text'] ?></div>
<?php endif; ?>

<!-- Форма для Q2/Q3/Q4 -->
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

<!-- Форма для TOUT -->
<form class="form-block" method="post">
  <h2>Добавить Tout ou Rien</h2>

  <div class="row">
    <label>Дата:</label>
    <input type="date" name="date_tout" required />
  </div>

  <div class="row tout-row">
    <?php for ($i = 1; $i <= 12; $i++): ?>
      <input type="number" name="t<?= $i ?>" min="1" max="24" required />
    <?php endfor; ?>
  </div>

  <button type="submit" name="submit_tout">Сохранить</button>
</form>

</body>
</html>