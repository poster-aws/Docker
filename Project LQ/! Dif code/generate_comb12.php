<?php
require_once "db.php"; // адаптируй путь под себя

function combinations($pool, $k) {
    $n = count($pool);
    $indices = range(0, $k - 1);

    while (true) {
        yield array_map(fn($i) => $pool[$i], $indices);

        $i = $k - 1;
        while ($i >= 0 && $indices[$i] == $n - $k + $i) $i--;
        if ($i < 0) break;

        $indices[$i]++;
        for ($j = $i + 1; $j < $k; $j++) {
            $indices[$j] = $indices[$j - 1] + 1;
        }
    }
}

$conn = new mysqli("db", "user", "user", "toutourien");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("
  INSERT INTO Comb12 (n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12)
  VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");

$count = 0;
foreach (combinations(range(1, 24), 12) as $combo) {
    $stmt->bind_param("iiiiiiiiiiii", ...$combo);
    $stmt->execute();
    $count++;

    if ($count % 10000 === 0) {
        echo "Inserted: $count\n";
        flush();
    }
}

$stmt->close();
$conn->close();
echo "Done: $count combinations inserted.\n";