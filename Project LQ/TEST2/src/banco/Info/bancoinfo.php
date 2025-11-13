<?php
require_once "../db.php"; // $bancoConn

// Загружаем данные comb2
$rows = [];
$sql = "SELECT * FROM comb2 ORDER BY Tirage DESC";
$result = $bancoConn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info</title>

<style>
/* ========================== */
/* === СТИЛИ ИЗ Q2 (ПОЛНЫЕ) === */
/* ========================== */

/* 📦 Общий контейнер таблиц */
.tables-wrapper {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  flex-wrap: nowrap;
}

/* 🔳 Обёртки таблиц */
.table-container,
.combo-table-container,
.number-stats-table {
  flex: 0 0 auto;
  width: max-content;
  max-height: 88vh;
  overflow: auto;
  margin-inline: auto;
  border-radius: 12px;
  border: 2px solid #a4a1a1;
}

.table-container {
  background: #00000005;
  box-shadow: 2px 4px 6px rgba(0, 0, 0, 0.3);
}

/* 📊 Общий стиль таблиц */
.interactive-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 18px;
  font-family: 'Shadows Into Light', cursive;
  color: #000000;
  scrollbar-width: thin;
  scrollbar-color: rgb(30, 0, 255) transparent;
}

/* 📌 Закреплённые заголовки */
.interactive-table thead th {
  position: sticky;
  padding: 9px;
  top: 0;
  background-color: rgb(163, 216, 234);
  z-index: 10;
  border-bottom: 2px solid #888;
}

/* 📋 Ячейки */
.interactive-table td {
  padding: 9px;
  border-bottom: 1px dashed #777777;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* 🖱️ Подсветка строк */
.interactive-table tr:hover {
  background-color: rgba(161, 161, 161, 0.493);
  transition: background-color 0.3s ease;
}

.interactive-table td:hover {
  background-color: rgba(202, 202, 202, 0.485);
}

/* 🔵 Шарик */
.circle {
  display: inline-block;
  width: 28px;
  height: 28px;
  line-height: 28px;
  border-radius: 50%;
  background-color: #7eb0ea;
  color: #000000;
  font-weight: bold;
  text-align: center;
  box-shadow: 0 0 3px rgba(0, 0, 0, 0.4);
  font-family: Arial, sans-serif;
}

/* 📉 Адаптивность */
@media (max-width: 768px) {
  .tables-wrapper {
    flex-direction: column;
    align-items: center;
    gap: 30px;
  }

  .table-container,
  .combo-table-container,
  .number-stats-table {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
  }
}

/* ========================== */
/* === Банко Инфо – базовое === */
/* ========================== */

.banco-info-page {
  font-family: sans-serif;
  margin: 0;
  padding: 0;
  text-align: center;
  background: transparent;
}

/* Меню выбора */
.menu-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin: 20px 0;
}

select {
  font-size: 14px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
  background: rgba(255, 255, 255, 0.9);
  cursor: pointer;
}

/* Инфо-блок */
.info-placeholder {
  max-width: 800px;
  margin: 15px auto;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.1);
  border-left: 4px solid #007BFF;
  border-radius: 6px;
  color: #333;
  font-size: 0.95em;
}
</style>
</head>

<body class="banco-info-page">

  <!-- Меню выбора -->
  <div class="menu-row">
    <select id="combinaisonSelect">
      <option value="c2">Combinaison de 2</option>
      <option value="c3">Combinaison de 3</option>
    </select>

    <select id="tirageSelect">
      <option value="dernier">Dernier tirage</option>
      <option value="200">200 tirages</option>
      <option value="tous">Tous les tirages</option>
    </select>
  </div>

  <!-- Инфо-блок -->
  <div class="info-placeholder">
    <p><i>Bloc d'information — section temporaire (en développement).</i></p>
  </div>

  <!-- ░░░ Таблица COMB2 — оформлена как Q2 ░░░ -->
  <div class="tables-wrapper">
    <div class="table-container">

      <table class="interactive-table">
        <thead>
          <tr>
            <th>Tirage</th>
            <th>#</th>
            <th>#</th>
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

            <td><span class="circle"><?= htmlspecialchars($r['n1']) ?></span></td>
            <td><span class="circle"><?= htmlspecialchars($r['n2']) ?></span></td>

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

</body>
</html>