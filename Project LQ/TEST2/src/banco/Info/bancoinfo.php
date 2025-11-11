<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Banco Info</title>

<style>
/* === Banco Info — базовая структура === */
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
    <p><i>Bloc d’information — section temporaire (en développement).</i></p>
  </div>

</body>
</html>