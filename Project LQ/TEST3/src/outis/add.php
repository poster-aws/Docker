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

$addLog = [];

function add_log(string $text, string $level = 'info'): void
{
    global $addLog;
    $addLog[] = ['text' => $text, 'level' => $level];
}

function add_mysqli_flush_results(mysqli $conn): void
{
    while ($conn->more_results()) {
        $conn->next_result();
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
}

function add_call_procedure(mysqli $conn, string $proc): bool
{
    add_log("CALL {$proc}()", 'cmd');
    if (!$conn->query("CALL {$proc}()")) {
        add_log('  → Ошибка: ' . $conn->error, 'error');
        return false;
    }
    add_mysqli_flush_results($conn);
    add_log('  → OK', 'ok');
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  /* ===================== Q2/Q3/Q4 ===================== */
  if (isset($_POST['submit_q'])) {
    add_log('[Q2 / Q3 / Q4] Старт', 'info');
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
    add_log("Дата: {$date}", 'info');
    add_log("Q2: {$n1}-{$n2} | Q3: {$n3}-{$n4}-{$n5} | Q4: {$n6}-{$n7}-{$n8}-{$n9}", 'info');

    $q2_exists = $conn->query("SELECT 1 FROM Q2 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q3_exists = $conn->query("SELECT 1 FROM Q3 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    $q4_exists = $conn->query("SELECT 1 FROM Q4 WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
    add_log('Проверка даты в Q2, Q3, Q4…', 'cmd');

    if ($q2_exists && $q3_exists && $q4_exists) {
      add_log('Дата уже существует во всех таблицах', 'warn');
    } else {
      add_log('INSERT Q2…', 'cmd');
      $stmt = $conn->prepare("REPLACE INTO Q2 (Tirage, n1, n2) VALUES (?, ?, ?)");
      if ($stmt) { $stmt->bind_param("sii", $date, $n1, $n2); $stmt->execute(); $stmt->close(); add_log('  → OK', 'ok'); }

      add_log('INSERT Q3…', 'cmd');
      $stmt = $conn->prepare("REPLACE INTO Q3 (Tirage, n1, n2, n3) VALUES (?, ?, ?, ?)");
      if ($stmt) { $stmt->bind_param("siii", $date, $n3, $n4, $n5); $stmt->execute(); $stmt->close(); add_log('  → OK', 'ok'); }

      add_log('INSERT Q4…', 'cmd');
      $stmt = $conn->prepare("REPLACE INTO Q4 (Tirage, n1, n2, n3, n4) VALUES (?, ?, ?, ?, ?)");
      if ($stmt) { $stmt->bind_param("siiii", $date, $n6, $n7, $n8, $n9); $stmt->execute(); $stmt->close(); add_log('  → OK', 'ok'); }

      add_log('Пересчёт статистики (13 процедур)…', 'info');
      $procedures = [
        'fill_Q2_stats_order', 'fill_Q2_stats_norder', 'fill_Q2_combo_stats_order', 'fill_Q2_combo_stats_norder',
        'fill_Q3_stats_order', 'fill_Q3_stats_norder', 'fill_Q3_combo_stats_order', 'fill_Q3_combo_stats_norder',
        'fill_Q4_fois', 'fill_Q4_stats_order', 'fill_Q4_stats_norder', 'fill_Q4_combo_stats_order', 'fill_Q4_combo_stats_norder'
      ];
      foreach ($procedures as $proc) {
        add_call_procedure($conn, $proc);
      }

      add_log('Тираж сохранён', 'ok');
    }
  }

  /* ===================== Tout ===================== */
  if (isset($_POST['tout-submit'])) {
    add_log('[Tout ou Rien] Старт', 'info');
    $date = $_POST['tout_date'] ?? null;
    $selected = explode(',', $_POST['tout_selected'] ?? '');
    $numbers = array_map('intval', $selected);
    add_log("Дата: {$date}", 'info');

    if (count($numbers) !== 12) {
      add_log('Ошибка: нужно выбрать ровно 12 чисел', 'error');
    } else {
      add_log('Числа: ' . implode(', ', $numbers), 'info');
      add_log('Проверка даты в Tout…', 'cmd');
      $exists = $toutConn->query("SELECT 1 FROM Tout WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
      if ($exists) {
        add_log('Дата уже существует', 'warn');
      } else {
        add_log('INSERT Tout…', 'cmd');
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
          add_log('  → OK', 'ok');
          add_log('Тираж сохранён', 'ok');
        }
      }
    }
  }

  /* ===================== Banco ===================== */
  if ($bancoAvailable && isset($_POST['banco-submit'])) {
    add_log('[Banco] Старт', 'info');
    $date = $_POST['banco_date'] ?? null;
    $turbo = intval($_POST['banco_turbo']);
    $selected = explode(',', $_POST['banco_selected'] ?? '');
    $numbers = array_map('intval', $selected);
    add_log("Дата: {$date} | Turbo: {$turbo}", 'info');

    if (count($numbers) !== 20) {
      add_log('Ошибка: нужно выбрать ровно 20 чисел', 'error');
    } else {
      add_log('Числа: ' . implode(', ', $numbers), 'info');
      add_log('Проверка даты в banco…', 'cmd');
      $exists = $bancoConn->query("SELECT 1 FROM banco WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;
      if ($exists) {
        add_log('Дата уже существует', 'warn');
      } else {
        add_log('INSERT banco…', 'cmd');
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
          add_log('  → OK', 'ok');
          add_log('Тираж сохранён', 'ok');
        }
      }
    }
  }

  /* ===================== Astro ===================== */
  if ($astroAvailable && isset($_POST['astro-submit'])) {
    add_log('[Astro] Старт', 'info');
    $date   = $_POST['astro_date'] ?? null;
    $jour   = intval($_POST['astro_jour']);
    $mois   = $_POST['astro_mois'] ?? null;
    $annee  = intval($_POST['astro_annee']);
    $signe  = $_POST['astro_signe'] ?? null;
    add_log("Дата: {$date} | {$jour} {$mois} {$annee} | {$signe}", 'info');

    if ($jour < 1 || $jour > 31 || $annee < 0 || $annee > 99) {
      add_log('Ошибка: данные Astro недопустимы', 'error');
    } else {
      add_log('Проверка даты в Astro…', 'cmd');
      $exists = $astroConn->query("SELECT 1 FROM Astro WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

      if ($exists) {
        add_log('Дата уже существует', 'warn');
      } else {
        add_log('INSERT Astro…', 'cmd');
        $stmt = $astroConn->prepare(
          "REPLACE INTO Astro (Tirage, jour, mois, annee, signe)
           VALUES (?, ?, ?, ?, ?)"
        );
        if ($stmt) {
          $stmt->bind_param("sisis", $date, $jour, $mois, $annee, $signe);
          $stmt->execute();
          $stmt->close();
          add_log('  → OK', 'ok');
          add_log('Тираж сохранён', 'ok');
        }
      }
    }
  }

  /* ===================== Vie ===================== */
  if (isset($_POST['vie-submit'])) {
    add_log('[Grande Vie] Старт', 'info');
    $date = $_POST['vie_date'] ?? null;

    $selected = explode(',', $_POST['vie_selected'] ?? '');
    $numbers = array_map('intval', $selected);

    $gn = isset($_POST['vie_gn']) ? intval($_POST['vie_gn']) : -1;
    add_log("Дата: {$date}", 'info');

    if (count($numbers) !== 5) {
      add_log('Ошибка: нужно выбрать ровно 5 чисел', 'error');
    } elseif (count(array_unique($numbers)) !== 5) {
      add_log('Ошибка: числа должны быть уникальны', 'error');
    } elseif ($gn < 0 || $gn > 7) {
      add_log('Ошибка: GN должен быть от 0 до 7', 'error');
    } else {
      $vieValid = true;
      foreach ($numbers as $x) {
        if ($x < 1 || $x > 49) {
          add_log('Ошибка: числа должны быть от 1 до 49', 'error');
          $vieValid = false;
          break;
        }
      }

      if ($vieValid) {
        add_log('Числа: ' . implode(', ', $numbers) . " | GN: {$gn}", 'info');
        add_log('Проверка даты в Vie…', 'cmd');
        $exists = $vieConn->query("SELECT 1 FROM Vie WHERE Tirage = '$date' LIMIT 1")->num_rows > 0;

        if ($exists) {
          add_log('Дата уже существует', 'warn');
        } else {
          add_log('INSERT Vie…', 'cmd');
          $stmt = $vieConn->prepare(
            "REPLACE INTO Vie (Tirage, n1, n2, n3, n4, n5, GN)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
          );
          if ($stmt) {
            $n1 = $numbers[0]; $n2 = $numbers[1]; $n3 = $numbers[2]; $n4 = $numbers[3]; $n5 = $numbers[4];
            $stmt->bind_param("siiiiii", $date, $n1, $n2, $n3, $n4, $n5, $gn);
            $stmt->execute();
            $stmt->close();
            add_log('  → OK', 'ok');
            add_call_procedure($vieConn, 'fill_Vie_info');
            add_log('Тираж сохранён', 'ok');
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

$allowedPanels = ['q', 'tout', 'astro', 'banco', 'vie'];
$activePanel = 'q';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['submit_q'])) {
    $activePanel = 'q';
  } elseif (isset($_POST['tout-submit'])) {
    $activePanel = 'tout';
  } elseif (isset($_POST['astro-submit'])) {
    $activePanel = 'astro';
  } elseif (isset($_POST['banco-submit'])) {
    $activePanel = 'banco';
  } elseif (isset($_POST['vie-submit'])) {
    $activePanel = 'vie';
  }
} elseif (isset($_GET['lottery']) && in_array($_GET['lottery'], $allowedPanels, true)) {
  $activePanel = $_GET['lottery'];
}

function add_panel_class(string $id, string $active): string
{
    return $id === $active ? ' add-panel is-active' : ' add-panel';
}

function add_nav_class(string $id, string $active): string
{
    return $id === $active ? ' add-nav__item is-active' : ' add-nav__item';
}

function add_log_level_class(string $level): string
{
    $map = [
        'ok' => 'add-terminal__line--ok',
        'error' => 'add-terminal__line--error',
        'warn' => 'add-terminal__line--warn',
        'cmd' => 'add-terminal__line--cmd',
        'muted' => 'add-terminal__line--muted',
        'info' => 'add-terminal__line--info',
    ];
    return $map[$level] ?? 'add-terminal__line--info';
}

if (empty($addLog)) {
    $addLog = [
        ['text' => '> PosteR_Lab — add.php', 'level' => 'muted'],
        ['text' => '> Ожидание отправки формы…', 'level' => 'muted'],
    ];
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
    .add-layout {
      display: flex;
      gap: 20px;
      align-items: flex-start;
      max-width: 1400px;
    }
    .add-nav {
      display: flex;
      flex-direction: column;
      flex: 0 0 auto;
      align-self: flex-start;
      width: 210px;
      height: fit-content;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.12);
      padding: 12px 0;
      position: sticky;
      top: 20px;
    }
    .add-nav__title {
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      color: #64748b;
      padding: 4px 18px 10px;
    }
    .add-nav__list {
      list-style: none;
      flex: 0 0 auto;
    }
    .add-nav__list li {
      flex: 0 0 auto;
    }
    .add-nav__item {
      display: block;
      padding: 11px 18px;
      color: #1e293b;
      text-decoration: none;
      font-size: 15px;
      font-weight: 600;
      border-left: 3px solid transparent;
      transition: background 0.15s, border-color 0.15s;
    }
    .add-nav__item:hover {
      background: #f1f5f9;
    }
    .add-nav__item.is-active {
      background: #e8f0fe;
      border-left-color: #2563eb;
      color: #1d4ed8;
    }
    .add-nav__item.is-disabled {
      color: #94a3b8;
      pointer-events: none;
    }
    .add-main {
      flex: 0 0 400px;
      width: 400px;
    }
    .add-terminal {
      flex: 0 0 340px;
      width: 340px;
      background: #0f172a;
      color: #e2e8f0;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.25);
      position: sticky;
      top: 20px;
      max-height: calc(100vh - 40px);
      overflow: hidden;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }
    .add-terminal__body {
      padding: 14px;
      overflow: auto;
      height: 100%;
      max-height: calc(100vh - 40px);
      font-size: 12px;
      line-height: 1.55;
    }
    .add-terminal__line {
      margin: 0 0 4px;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .add-terminal__line--ok { color: #4ade80; }
    .add-terminal__line--error { color: #f87171; }
    .add-terminal__line--warn { color: #fbbf24; }
    .add-terminal__line--cmd { color: #93c5fd; }
    .add-terminal__line--info { color: #cbd5e1; }
    .add-terminal__line--muted { color: #64748b; }
    .add-panel {
      display: none;
    }
    .add-panel.is-active {
      display: block;
    }
    .add-panel .form-block {
      width: 400px;
      max-width: 400px;
    }
    .form-block {
      background: #fff;
      border-radius: 8px;
      padding: 14px 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      max-width: 100%;
    }
    .form-block h2 {
      margin-bottom: 15px;
    }
    .form-block--unavailable {
      color: #64748b;
      line-height: 1.5;
    }
    .row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    select, input[type="date"], button, input[type="text"] {
      padding: 5px 10px;
      font-size: 15px;
    }
    button {
      display: block;
      margin: 10px auto 0;
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

    @media (max-width: 760px) {
      .add-layout {
        flex-direction: column;
      }
      .add-nav {
        width: 100%;
        position: static;
      }
      .add-main {
        width: 100%;
        flex: none;
      }
      .add-terminal {
        width: 100%;
        flex: none;
        position: static;
        max-height: 280px;
      }
      .add-terminal__body {
        max-height: 280px;
      }
      .add-panel .form-block {
        width: 100%;
        max-width: 100%;
      }
      .add-nav__list {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding: 0 8px 8px;
      }
      .add-nav__title {
        padding-bottom: 8px;
      }
      .add-nav__item {
        border-left: none;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 14px;
      }
      .add-nav__item.is-active {
        border-left: none;
      }
    }
  </style>
</head>
<body>

<div class="add-layout">
  <nav class="add-nav" aria-label="Лотереи">
    <div class="add-nav__title">Лотереи</div>
    <ul class="add-nav__list">
      <li><a class="<?= trim(add_nav_class('q', $activePanel)) ?>" href="add.php?lottery=q">Q2 / Q3 / Q4</a></li>
      <li><a class="<?= trim(add_nav_class('tout', $activePanel)) ?>" href="add.php?lottery=tout">Tout ou Rien</a></li>
      <li>
        <?php if ($astroAvailable): ?>
          <a class="<?= trim(add_nav_class('astro', $activePanel)) ?>" href="add.php?lottery=astro">Astro</a>
        <?php else: ?>
          <span class="add-nav__item is-disabled">Astro</span>
        <?php endif; ?>
      </li>
      <li>
        <?php if ($bancoAvailable): ?>
          <a class="<?= trim(add_nav_class('banco', $activePanel)) ?>" href="add.php?lottery=banco">Banco</a>
        <?php else: ?>
          <span class="add-nav__item is-disabled">Banco</span>
        <?php endif; ?>
      </li>
      <li><a class="<?= trim(add_nav_class('vie', $activePanel)) ?>" href="add.php?lottery=vie">Grande Vie</a></li>
    </ul>
  </nav>

  <div class="add-main">

<section id="panel-q" class="<?= trim(add_panel_class('q', $activePanel)) ?>">
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
</section>

<section id="panel-tout" class="<?= trim(add_panel_class('tout', $activePanel)) ?>">
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
</section>

<section id="panel-astro" class="<?= trim(add_panel_class('astro', $activePanel)) ?>">
<?php if ($astroAvailable): ?>
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
<?php else: ?>
<div class="form-block form-block--unavailable">
  <h2>Astro</h2>
  <p>База Astro недоступна (нет подключения <code>astro/db.php</code>).</p>
</div>
<?php endif; ?>
</section>

<section id="panel-banco" class="<?= trim(add_panel_class('banco', $activePanel)) ?>">
<?php if ($bancoAvailable): ?>
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
<?php else: ?>
<div class="form-block form-block--unavailable">
  <h2>Banco</h2>
  <p>База Banco недоступна (нет подключения <code>banco/db.php</code>).</p>
</div>
<?php endif; ?>
</section>

<section id="panel-vie" class="<?= trim(add_panel_class('vie', $activePanel)) ?>">
<form class="form-block vie-form" method="post">
  <h2>Добавить Grande Vie</h2>

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
</section>

  </div>

  <aside class="add-terminal" aria-label="Журнал выполнения">
    <div class="add-terminal__body" id="add-terminal-body">
      <?php foreach ($addLog as $line): ?>
        <p class="add-terminal__line <?= htmlspecialchars(add_log_level_class($line['level']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($line['text'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>
    </div>
  </aside>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const terminalBody = document.getElementById('add-terminal-body');
  if (terminalBody) {
    terminalBody.scrollTop = terminalBody.scrollHeight;
  }

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
