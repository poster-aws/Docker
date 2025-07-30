<?php
// toutourien/tout.php — сетка как Q3info, но без iframe

$conn = new mysqli("db", "user", "user", "toutourien");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$allowedLimits = [100, 200, 500];
$limit = (isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits)) ? (int)$_GET['limit'] : 100;

$sql = "SELECT Tirage, n1,n2,n3,n4,n5,n6,n7,n8,n9,n10,n11,n12 FROM Tout ORDER BY Tirage DESC LIMIT $limit";
$res = $conn->query($sql);

$tirages = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $tirages[] = [
            'Tirage' => $row['Tirage'],
            'nums' => array_map('intval', [
                $row['n1'], $row['n2'], $row['n3'], $row['n4'],
                $row['n5'], $row['n6'], $row['n7'], $row['n8'],
                $row['n9'], $row['n10'], $row['n11'], $row['n12']
            ])
        ];
    }
}
$conn->close();
?>
<div id="tout-meta" data-count="<?= count($tirages) ?>"></div>

<form style="text-align:right; margin-bottom:0.5em;">
  <label for="limit">Tirages :</label>
  <select id="limit" name="limit" onchange="location.href='tout.php?limit=' + this.value">
    <?php foreach ($allowedLimits as $opt): ?>
      <option value="<?= $opt ?>" <?= $limit == $opt ? 'selected' : '' ?>><?= $opt ?></option>
    <?php endforeach; ?>
  </select>
</form>

<div class="table-wrapper">
  <table class="digit-grid">
    <thead>
      <tr>
        <th>#</th>
        <?php foreach ($tirages as $t): ?>
          <th><?= htmlspecialchars($t['Tirage']) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php for ($n = 1; $n <= 24; $n++): ?>
        <tr>
          <th><?= $n ?></th>
          <?php foreach ($tirages as $t):
            $count = array_count_values($t['nums'])[$n] ?? 0;
            $class = $count === 1 ? 'hit' : '';
          ?>
            <td class="<?= $class ?>"><?= $count > 0 ? $n : '' ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endfor; ?>
    </tbody>
  </table>
</div>

<style>
  .table-wrapper {
    width: 100%;
    overflow-x: auto;
    background: rgba(173, 216, 230, 0.85);
    border-radius: 6px;
    border: 1px solid #ccc;
    padding: 1em;
  }

  .digit-grid {
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
  }

  .digit-grid th,
  .digit-grid td {
    width: 22px;
    height: 22px;
    text-align: center;
    border: 1px solid #ccc;
    padding: 0;
    box-sizing: border-box;
  }

  .digit-grid th {
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    font-size: 0.7em;
    background: #eee;
  }

  .digit-grid td.hit {
    background-color: #7eb0ea;
    font-weight: bold;
  }

  .digit-grid th:first-child,
  .digit-grid td:first-child {
    background-color: #eee;
    font-weight: bold;
    position: sticky;
    left: 0;
    z-index: 1;
  }
</style>