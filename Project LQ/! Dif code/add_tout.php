<?php
require_once "toutourien/db.php"; 
error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $numbers = [];

    // Сбор чисел n1..n12
    for ($i = 1; $i <= 12; $i++) {
        $numbers[] = (int)$_POST["n$i"];
    }

    // Проверка на существующую дату
    $stmt = $conn->prepare("SELECT 1 FROM Tout WHERE Tirage = ? LIMIT 1");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $msg = ['class' => 'error', 'text' => "⚠️ Запись с такой датой уже существует."];
    } else {
        // Подготовка и вставка
        $sql = "INSERT INTO Tout (Tirage, n1, n2, n3, n4, n5, n6, n7, n8, n9, n10, n11, n12)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        // Подготовка параметров
        $types = "s" . str_repeat("i", 12);
        $params = array_merge([$types, $date], $numbers);

        // Преобразуем в массив ссылок
        $bind = [];
        foreach ($params as $key => &$val) {
            $bind[$key] = &$val;
        }

        // Привязка параметров
        call_user_func_array([$stmt, 'bind_param'], $bind);

        if ($stmt->execute()) {
            $msg = ['class' => 'success', 'text' => "✅ Данные успешно добавлены."];
        } else {
            $msg = ['class' => 'error', 'text' => "❌ Ошибка при добавлении данных."];
        }
        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <title>Добавить тираж в Tout</title>
  <style>
    body {
      font-family: sans-serif;
      background: #eef;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
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
    input[type="date"],
    input[type="number"] {
      padding: 6px;
      font-size: 16px;
      width: 100%;
    }
    .numbers {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
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

  <form method="post">
    <h2 style="text-align:center;">Добавление тиража Tout</h2>

    <?php if ($msg): ?>
      <div class="msg <?= $msg['class'] ?>"><?= $msg['text'] ?></div>
    <?php endif; ?>

    <input type="date" name="date" required />

    <div class="numbers">
      <?php for ($i = 1; $i <= 12; $i++): ?>
        <input type="number" name="n<?= $i ?>" min="1" max="24" required placeholder="n<?= $i ?>" />
      <?php endfor; ?>
    </div>

    <button type="submit">Добавить</button>
  </form>

</body>
</html>