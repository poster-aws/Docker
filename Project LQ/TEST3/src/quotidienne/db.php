<?php
$servername = "db";
$username   = "user";
$password   = "user";
$dbname     = "quotidienne";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

/**
 * Compteurs de tirages depuis Q_info (rempli par fill_Q_info).
 *
 * @return array{Q2: int, Q3: int, Q4: int}
 */
function quotidienne_q_info_counts(mysqli $conn): array
{
    $counts = ['Q2' => 0, 'Q3' => 0, 'Q4' => 0];
    $tableExists = $conn->query("SHOW TABLES LIKE 'Q_info'");
    if ($tableExists && $tableExists->num_rows > 0) {
        $res = $conn->query('SELECT Q2, Q3, Q4 FROM Q_info LIMIT 1');
        if ($res && $row = $res->fetch_assoc()) {
            $counts['Q2'] = (int) ($row['Q2'] ?? 0);
            $counts['Q3'] = (int) ($row['Q3'] ?? 0);
            $counts['Q4'] = (int) ($row['Q4'] ?? 0);
            return $counts;
        }
    }
    foreach (['Q2', 'Q3', 'Q4'] as $table) {
        $res = $conn->query("SELECT COUNT(*) AS total FROM {$table}");
        if ($res && $row = $res->fetch_assoc()) {
            $counts[$table] = (int) ($row['total'] ?? 0);
        }
    }
    return $counts;
}

function quotidienne_q_info_count(mysqli $conn, string $game): int
{
    $game = strtoupper($game);
    if (!in_array($game, ['Q2', 'Q3', 'Q4'], true)) {
        return 0;
    }
    $counts = quotidienne_q_info_counts($conn);
    return $counts[$game];
}
?>
