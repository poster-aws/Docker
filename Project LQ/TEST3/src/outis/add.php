<?php
/**
 * Копия TEST2/src/outis/add.php для TEST3.
 * Banco и Astro подключаются только если есть ../banco/db.php и ../astro/db.php.
 */
require_once __DIR__ . '/../quotidienne/db.php';
require_once __DIR__ . '/../toutourien/db.php';
$bancoAvailable = is_file(__DIR__ . '/../banco/db.php');
$astroAvailable = is_file(__DIR__ . '/../astro/db.php');
if ($bancoAvailable) {
    require_once __DIR__ . '/../banco/db.php';
}
if ($astroAvailable) {
    require_once __DIR__ . '/../astro/db.php';
}
require_once __DIR__ . '/../vie/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = null;
$toutMsg = null;
$bancoMsg = null;
$astroMsg = null;
$vieMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* ===================== Q2/Q3/Q4 ===================== */
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
        'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order', 'fill_Q2_combo_stats_norder',
        'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order', 'fill_Q3_combo_stats_norder',
        'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order', 'fill_Q4_combo_stats_norder'
      ];
      foreach ($procedures as $proc) $conn->query("CALL $proc()");

      $msg = ['class' => 'success', 'text' => "✅ Q2/Q3/Q4: данные добавлены."];
    }
  }

  /* ===================== Tout ===================== */
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
          $stmt->execute();
          $stmt->close();
          $toutMsg = ['class' => 'success', 'text' => "✅ Tout: данные добавлены."];
        }
      }
    }
  }

  /* ===================== Banco ===================== */
  if ($bancoAvailable && isset($_POST['banco-submit'])) {
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
          $types = "s" . str_repeat("i", 21);
          $params = array_merge([$date], $numbers, [$turbo]);

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

  /* ===================== Astro ===================== */
  if ($astroAvailable && isset($_POST['astro-submit'])) {
    $date   = $_POST['astro_date'] ?? null;
    $jour   = intval($_POST['astro_jour']);
    $mois   = $_POST['astro_mois'] ?? null;
    $annee  = intval($_POST['astro_annee']);
    $signe  = $_POST['astro_signe'] ?? null;

    if ($jour < 1 || $jour > 31 || $annee < 0 || $annee > 99) {
      $astroMsg = ['class' => 'error', 'text' => '❌ Données Astro invalides.'];
    } else {
      $exists = $astroConn->query("SELECT 1 FROM Astro WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

      if ($exists) {
        $astroMsg = ['class' => 'error', 'text' => '⚠️ Astro: дата уже существует.'];
      } else {
        $stmt = $astroConn->prepare(
          "REPLACE INTO Astro (Tirage, jour, mois, annee, signe)
           VALUES (?, ?, ?, ?, ?)"
        );
        if ($stmt) {
          $stmt->bind_param("sisis", $date, $jour, $mois, $annee, $signe);
          $stmt->execute();
          $stmt->close();
          $astroMsg = ['class' => 'success', 'text' => '✅ Astro: данные добавлены.'];
        }
      }
    }
  }

  /* ===================== Vie ===================== */
  if (isset($_POST['vie-submit'])) {
    $date = $_POST['vie_date'] ?? null;

    $selected = explode(',', $_POST['vie_selected'] ?? '');
    $numbers = array_map('intval', $selected);

    $gn = isset($_POST['vie_gn']) ? intval($_POST['vie_gn']) : -1;

    if (count($numbers) !== 5) {
      $vieMsg = ['class' => 'error', 'text' => '❌ Нужно выбрать ровно 5 чисел (1–49).'];
    } elseif (count(array_unique($numbers)) !== 5) {
      $vieMsg = ['class' => 'error', 'text' => '❌ Числа Vie должны быть уникальны.'];
    } elseif ($gn < 0 || $gn > 7) {
      $vieMsg = ['class' => 'error', 'text' => '❌ GN должен быть от 0 до 7.'];
    } else {

      foreach ($numbers as $x) {
        if ($x < 1 || $x > 49) {
          $vieMsg = ['class' => 'error', 'text' => '❌ Числа Vie должны быть от 1 до 49.'];
          break;
        }
      }

      if ($vieMsg === null) {
        $exists = $vieConn->query("SELECT 1 FROM Vie WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

        if ($exists) {
          $vieMsg = ['class' => 'error', 'text' => '⚠️ Vie: дата уже существует.'];
        } else {
          $stmt = $vieConn->prepare(
            "REPLACE INTO Vie (Tirage, n1, n2, n3, n4, n5, GN)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
          );
          if ($stmt) {
            $n1 = $numbers[0]; $n2 = $numbers[1]; $n3 = $numbers[2]; $n4 = $numbers[3]; $n5 = $numbers[4];
            $stmt->bind_param("siiiiii", $date, $n1, $n2, $n3, $n4, $n5, $gn);
            $stmt->execute();
            $stmt->close();
            $vieMsg = ['class' => 'success', 'text' => '✅ Vie: данные добавлены.'];
          }
        }
      }
    }
  }

  $conn->close();
  $toutConn->close();
  if ($bancoAvailable) {
    $bancoConn->close();
  }
  if ($astroAvailable) {
    $astroConn->close();
  }
  $vieConn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Добавление тиражей</title>
  <link rel="stylesheet" href="../poster-lab-tag.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body {
      font-family: sans-serif;
      background: #e0e0e0;
      padding: 20px;
    }
    .back-home { margin-bottom: 16px; }
    .back-home a { color: #1a56db; }
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
    select, input[type="date"], button, input[type="text"] {
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
      user-select: none;
    }
    .circle.selected {
      background: #2e8b57;
      color: white;
    }

    .circle.gn-zero {
      background: #ffd966;
    }
    .circle.gn.selected {
      background: #b45f06;
      color: white;
    }
  </style>
</head>
<body>

<p class="back-home"><a href="../index.html">← Accueil (application principale)</a></p>

<?php if (!empty($msg)): ?>
  <div class="msg <?= $msg['class'] ?>"><?= $msg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($toutMsg)): ?>
  <div class="msg <?= $toutMsg['class'] ?>"><?= $toutMsg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($bancoMsg)): ?>
  <div class="msg <?= $bancoMsg['class'] ?>"><?= $bancoMsg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($astroMsg)): ?>
  <div class="msg <?= $astroMsg['class'] ?>"><?= $astroMsg['text'] ?></div>
<?php endif; ?>
<?php if (!empty($vieMsg)): ?>
  <div class="msg <?= $vieMsg['class'] ?>"><?= $vieMsg['text'] ?></div>
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
<form class="form-block tout-form" method="post">
  <h2>Добавить Tout ou Rien</h2>
  <div class="row">
    <label>Дата:</label>
    <input type="date" name="tout_date" required />
  </div>
  <div class="circles tout-circles">
    <?php for ($i = 1; $i <= 24; $i++): ?>
      <div class="circle" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>
  <input type="hidden" name="tout_selected" id="tout_selected" required />
  <button type="submit" name="tout-submit">Сохранить Tout</button>
</form>

<?php if ($bancoAvailable): ?>
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
<?php endif; ?>

<?php if ($astroAvailable): ?>
<!-- Astro -->
<form class="form-block astro-form" method="post">
  <h2>Добавить Astro</h2>

  <div class="row">
    <label>Дата:</label>
    <input type="date" name="astro_date" required>
  </div>

  <div class="row">
    <label>Jour:</label>
    <select name="astro_jour">
      <?php for ($i=1;$i<=31;$i++) echo "<option>$i</option>"; ?>
    </select>
  </div>

  <div class="row">
    <label>Mois:</label>
    <select name="astro_mois">
      <?php
      $mois = [
        "Janvier","Février","Mars","Avril","Mai","Juin",
        "Juillet","Août","Septembre","Octobre","Novembre","Décembre"
      ];
      foreach ($mois as $m) echo "<option>$m</option>";
      ?>
    </select>
  </div>

  <div class="row">
    <label>Année:</label>
    <input
      type="text"
      name="astro_annee"
      maxlength="2"
      pattern="[0-9]{2}"
      placeholder="00"
      required
      style="width:60px; text-align:center;"
    >
  </div>

  <div class="row">
    <label>Signe:</label>
    <select name="astro_signe">
      <?php
      $signes = [
        "BÉLIER","TAUREAU","GÉMEAUX","CANCER","LION","VIERGE",
        "BALANCE","SCORPION","SAGITTAIRE","CAPRICORNE","VERSEAU","POISSONS"
      ];
      foreach ($signes as $s) echo "<option>$s</option>";
      ?>
    </select>
  </div>

  <button type="submit" name="astro-submit">Сохранить Astro</button>
</form>
<?php endif; ?>

<!-- Vie -->
<form class="form-block vie-form" method="post">
  <h2>Добавить Vie</h2>

  <div class="row">
    <label>Дата:</label>
    <input type="date" name="vie_date" required />
  </div>

  <div class="circles vie-circles" style="grid-template-columns: repeat(7, 1fr);">
    <?php for ($i = 1; $i <= 49; $i++): ?>
      <div class="circle" data-num="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <div class="row">
    <label>GN:</label>
  </div>
  <div class="circles gn-circles" style="grid-template-columns: repeat(7, 1fr); margin-top: -5px;">
    <?php for ($i = 1; $i <= 7; $i++): ?>
      <div class="circle gn" data-gn="<?= $i ?>"><?= $i ?></div>
    <?php endfor; ?>
  </div>

  <div class="circles gn-circles" style="grid-template-columns: 1fr; margin-top: 6px;">
    <div class="circle gn gn-zero" data-gn="0">0</div>
  </div>

  <input type="hidden" name="vie_selected" id="vie_selected" required />
  <input type="hidden" name="vie_gn" id="vie_gn" required />

  <button type="submit" name="vie-submit">Сохранить Vie</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {

  const toutCircles = document.querySelectorAll('.tout-circles .circle');
  const toutHiddenInput = document.getElementById('tout_selected');

  toutCircles.forEach(circle => {
    circle.addEventListener('click', () => {
      if (circle.classList.contains('selected')) {
        circle.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.tout-circles .circle.selected');
        if (selected.length >= 12) return;
        circle.classList.add('selected');
      }

      const selectedValues = Array.from(document.querySelectorAll('.tout-circles .circle.selected'))
        .map(el => el.dataset.num);
      toutHiddenInput.value = selectedValues.join(',');
    });
  });

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
      if (bancoHiddenInput) bancoHiddenInput.value = selectedValues.join(',');
    });
  });

  const vieCircles = document.querySelectorAll('.vie-circles .circle');
  const vieHidden = document.getElementById('vie_selected');

  vieCircles.forEach(circle => {
    circle.addEventListener('click', () => {
      if (circle.classList.contains('selected')) {
        circle.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.vie-circles .circle.selected');
        if (selected.length >= 5) return;
        circle.classList.add('selected');
      }

      const selectedValues = Array.from(document.querySelectorAll('.vie-circles .circle.selected'))
        .map(el => el.dataset.num);
      vieHidden.value = selectedValues.join(',');
    });
  });

  const gnCircles = document.querySelectorAll('.gn-circles .circle');
  const gnHidden = document.getElementById('vie_gn');

  gnCircles.forEach(circle => {
    circle.addEventListener('click', () => {
      gnCircles.forEach(c => c.classList.remove('selected'));
      circle.classList.add('selected');
      gnHidden.value = circle.dataset.gn;
    });
  });

});
</script>

<aside class="poster-lab-tag">
  <a class="poster-lab-tag__text" href="add.php" title="Ajout des tirages">PosteR_Lab</a>
</aside>

</body>
</html>
