<?php
// Подключение к базе данных
$servername = "db";
$username = "user";
$password = "user";
$dbname = "quotidienne2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Определяем таблицу: Q2_stats_order или Q2_stats_norder
$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$table = $isNorder ? 'Q2_stats_norder' : 'Q2_stats_order';

// Получаем данные
$sql = "SELECT * FROM $table ORDER BY Tirage DESC";
$result = $conn->query($sql);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
$conn->close();

// Загружаем шаблон test.html
ob_start();
include 'test.html';
$template = ob_get_clean();

// Строим HTML-таблицу
$tableHTML = '';
if (!empty($data)) {
    foreach ($data as $row) {
        $tableHTML .= '<tr>';
        foreach ($row as $key => $cell) {
            if ($key === 'n1' || $key === 'n2') {
                $tableHTML .= '<td><span class="circle">' . htmlspecialchars($cell) . '</span></td>';
            } else {
                $tableHTML .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
        }
        $tableHTML .= '</tr>';
    }
} else {
    $tableHTML .= '<tr><td colspan="100%">Нет данных</td></tr>';
}

// Скрипт переключателя
$switchScript = "<script>
  const toggle = document.getElementById('toggleSwitch');
  const text = document.getElementById('switchText');
  toggle.checked = " . ($isNorder ? 'true' : 'false') . ";
  text.textContent = toggle.checked ? 'Вкл' : 'Выкл';
  toggle.addEventListener('change', () => {
    const next = toggle.checked ? '?norder=1' : '';
    window.location.href = 'index.php' + next;
  });
</script>";

// Подстановка плейсхолдеров
echo str_replace(
  ['<!--TABLE_PLACEHOLDER-->', '<!--SCRIPT_PLACEHOLDER-->'],
  [$tableHTML, $switchScript],
  $template
);