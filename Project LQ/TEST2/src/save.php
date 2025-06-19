<?php
require_once "db.php";

echo "<!DOCTYPE html><html lang='ru'><head><meta charset='UTF-8'><title>Результат</title>
<style>
  body {
    font-family: sans-serif;
    padding: 30px;
    background: #f9f9f9;
    text-align: center;
  }
  .message {
    margin-bottom: 20px;
    font-size: 1.2em;
  }
  button {
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
  }
</style>
</head><body>";

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

    // Q2 insert
    $stmt = $conn->prepare("INSERT INTO Q2 (Tirage, n1, n2)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2)");
    $stmt->bind_param("sii", $date, $n1, $n2);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;

    // Q3 insert
    $stmt = $conn->prepare("INSERT INTO Q3 (Tirage, n1, n2, n3)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE n1 = VALUES(n1), n2 = VALUES(n2), n3 = VALUES(n3)");
    $stmt->bind_param("siii", $date, $n3, $n4, $n5);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;

    // Q4 insert
    $stmt = $conn->prepare("INSERT INTO Q4 (Tirage, n1, n2, n3, n4)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            n1 = VALUES(n1),
            n2 = VALUES(n2),
            n3 = VALUES(n3),
            n4 = VALUES(n4)");
    $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9);
    $stmt->execute();
    if ($stmt->affected_rows > 0) $inserted = true;

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
        echo "<div class='message'>✅ Данные успешно сохранены и обновлены.</div>";
    } else {
        echo "<div class='message'>⚠️ Данные не были изменены.</div>";
    }

    $conn->close();
} else {
    echo "<div class='message'>Неверный метод запроса.</div>";
}

// Кнопка возврата
echo "<button onclick=\"window.location.href='save.html'\">Назад</button>";

echo "</body></html>";
?>