<?php
require_once 'db.php';

$isNorder = isset($_GET['norder']) && $_GET['norder'] === '1';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$table = $isNorder ? 'Q2_stats_norder' : 'Q2_stats_order';

// Ограничим лимит безопасным диапазоном
if ($limit < 100 || $limit > 99999) $limit = 100;

$query = "SELECT days, LPAD(CONCAT(n1, n2), 2, '0') AS combo FROM $table ORDER BY Tirage DESC LIMIT $limit";
$result = $conn->query($query);

$data = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = ['days' => (int)$row['days'], 'combo' => $row['combo']];
  }
}

header('Content-Type: application/json');
echo json_encode($data);
?>
