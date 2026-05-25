<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

function bancoinfo_table_exists(mysqli $conn, string $table): bool
{
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$safe'");
    return $res && $res->num_rows > 0;
}

$combo = isset($_GET['combo']) ? (string) $_GET['combo'] : 'c2';
if (!in_array($combo, ['c2', 'c3', 'c4'], true)) {
    $combo = 'c2';
}

$scope = isset($_GET['scope']) ? (string) $_GET['scope'] : 'dernier';
if (!in_array($scope, ['dernier', 'tous'], true)) {
    $scope = 'dernier';
}

$comboConfigs = [
    'c2' => [
        'table' => 'comb2',
        'fields' => ['n1', 'n2'],
        'maxSel' => 2,
        'statsAll' => 'bancoinfo.stats.c2.all',
        'statsPerDraw' => 'bancoinfo.stats.c2.per_draw',
        'serverFilterOnTous' => false,
    ],
    'c3' => [
        'table' => 'comb3',
        'fields' => ['n1', 'n2', 'n3'],
        'maxSel' => 3,
        'statsAll' => 'bancoinfo.stats.c3.all',
        'statsPerDraw' => 'bancoinfo.stats.c3.per_draw',
        'serverFilterOnTous' => true,
    ],
    'c4' => [
        'table' => 'comb4',
        'fields' => ['n1', 'n2', 'n3', 'n4'],
        'maxSel' => 4,
        'statsAll' => 'bancoinfo.stats.c4.all',
        'statsPerDraw' => 'bancoinfo.stats.c4.per_draw',
        'serverFilterOnTous' => true,
    ],
];

$config = $comboConfigs[$combo];
$table = $config['table'];
$fields = $config['fields'];
$maxSel = (int) $config['maxSel'];

$selectedNums = [];
if ($scope === 'tous' && isset($_GET['sel']) && $_GET['sel'] !== '') {
    foreach (explode(',', (string) $_GET['sel']) as $part) {
        $n = (int) trim($part);
        if ($n >= 1 && $n <= 70) {
            $selectedNums[] = $n;
        }
    }
    $selectedNums = array_values(array_unique($selectedNums));
}

$bancoCount = 0;
$tableExists = bancoinfo_table_exists($bancoConn, 'banco');
if ($tableExists) {
    $countRes = $bancoConn->query('SELECT COUNT(*) AS total FROM banco');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $bancoCount = (int) $row['total'];
    }
}

$lastNums = [];
$lastNumsSet = [];
if ($tableExists) {
    $resLast = $bancoConn->query('SELECT * FROM banco ORDER BY Tirage DESC LIMIT 1');
    if ($resLast && $resLast->num_rows > 0) {
        $rowLast = $resLast->fetch_assoc();
        for ($i = 1; $i <= 20; $i++) {
            if (!empty($rowLast["n$i"])) {
                $lastNums[] = (int) $rowLast["n$i"];
            }
        }
        $lastNumsSet = array_flip($lastNums);
    }
}

$combTableExists = bancoinfo_table_exists($bancoConn, $table);
$rows = [];
$statsMinFois = null;
$statsMaxFois = null;
$statsMaxDays = null;
$statsMaxMax = null;

if ($combTableExists) {
    if ($scope === 'dernier') {
        $sql = "SELECT * FROM `$table` WHERE days = 0 ORDER BY Tirage DESC";
        $result = $bancoConn->query($sql);
        if ($result && $result->num_rows > 0) {
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
        }
    } elseif ($scope === 'tous') {
        if (!$config['serverFilterOnTous']) {
            $sql = "SELECT * FROM `$table` ORDER BY Tirage DESC";
            $result = $bancoConn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($r = $result->fetch_assoc()) {
                    $rows[] = $r;
                }
            }
        } elseif (!empty($selectedNums)) {
            if (count($selectedNums) === 1) {
                $a = (int) $selectedNums[0];
                $conds = [];
                foreach ($fields as $f) {
                    $conds[] = "$f = $a";
                }
                $sql = 'SELECT * FROM `' . $table . '` WHERE ' . implode(' OR ', $conds) . ' ORDER BY Tirage DESC';
            } else {
                $inList = implode(',', array_map('intval', $selectedNums));
                $conds = [];
                foreach ($fields as $f) {
                    $conds[] = "$f IN ($inList)";
                }
                $sql = 'SELECT * FROM `' . $table . '` WHERE ' . implode(' OR ', $conds) . ' ORDER BY Tirage DESC';
            }
            $result = $bancoConn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($r = $result->fetch_assoc()) {
                    $rowNums = [];
                    foreach ($fields as $f) {
                        $rowNums[] = (int) $r[$f];
                    }
                    $ok = true;
                    foreach ($selectedNums as $sn) {
                        if (!in_array($sn, $rowNums, true)) {
                            $ok = false;
                            break;
                        }
                    }
                    if ($ok) {
                        $rows[] = $r;
                    }
                }
            }
        }
    }

    $resStats = $bancoConn->query(
        "SELECT MIN(fois) AS minFois, MAX(fois) AS maxFois, MAX(days) AS maxDays, MAX(`max`) AS maxMax FROM `$table`"
    );
    if ($resStats && $resStats->num_rows > 0) {
        $rowStats = $resStats->fetch_assoc();
        $statsMinFois = $rowStats['minFois'] !== null ? (int) $rowStats['minFois'] : null;
        $statsMaxFois = $rowStats['maxFois'] !== null ? (int) $rowStats['maxFois'] : null;
        $statsMaxDays = $rowStats['maxDays'] !== null ? (int) $rowStats['maxDays'] : null;
        $statsMaxMax = $rowStats['maxMax'] !== null ? (int) $rowStats['maxMax'] : null;
    }
}

$showFilterMessage = $config['serverFilterOnTous'] && $scope === 'tous' && empty($selectedNums);
$selParam = !empty($selectedNums) ? implode(',', $selectedNums) : '';

$bancoConn->close();

$line1Text = t($config['statsAll']);
$line2Text = t($config['statsPerDraw']);
$line3Text = ($statsMinFois !== null && $statsMaxFois !== null)
    ? sprintf(t('bancoinfo.stats.minmax_fois'), $statsMinFois, $statsMaxFois)
    : '—';
$line4Text = $statsMaxDays !== null
    ? sprintf(t('bancoinfo.stats.max_days'), $statsMaxDays)
    : '—';
$line5Text = $statsMaxMax !== null
    ? sprintf(t('bancoinfo.stats.max_max'), $statsMaxMax)
    : '—';
?>
<div id="banco-meta" data-count="<?= (int) $bancoCount ?>" data-header-sub-fr="<?= htmlspecialchars(t_for_lang('banco.header.sub', 'fr'), ENT_QUOTES, 'UTF-8') ?>" data-header-sub-en="<?= htmlspecialchars(t_for_lang('banco.header.sub', 'en'), ENT_QUOTES, 'UTF-8') ?>"></div>

<div
  id="bancoinfo-bootstrap"
  hidden
  data-combo="<?= htmlspecialchars($combo, ENT_QUOTES, 'UTF-8') ?>"
  data-scope="<?= htmlspecialchars($scope, ENT_QUOTES, 'UTF-8') ?>"
  data-sel="<?= htmlspecialchars($selParam, ENT_QUOTES, 'UTF-8') ?>"
  data-max-sel="<?= (int) $maxSel ?>"
  data-server-filter="<?= $config['serverFilterOnTous'] ? '1' : '0' ?>"
></div>

<div class="banco-layout banco-layout--info">
  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>

  <div class="banco-info-layout">
    <div class="banco-info-left">
      <div class="banco-info-block banco-info-block--grid">
        <div class="banco-numbers-grid" id="bancoInfoNumbersGrid">
          <?php for ($i = 1; $i <= 70; $i++):
              $isLast = isset($lastNumsSet[$i]);
          ?>
            <span
              class="banco-filter-num<?= $isLast ? ' in-last' : '' ?>"
              data-num="<?= $i ?>"
              role="button"
              tabindex="0"
            ><?= $i ?></span>
          <?php endfor; ?>
        </div>

        <div class="banco-info-actions">
          <button type="button" id="bancoInfoExecute"><?= htmlspecialchars(t('bancoinfo.filter.execute'), ENT_QUOTES, 'UTF-8') ?></button>
          <button type="button" id="bancoInfoReset"><?= htmlspecialchars(t('bancoinfo.filter.reset'), ENT_QUOTES, 'UTF-8') ?></button>
        </div>

        <div class="banco-info-menu">
          <select id="bancoInfoCombo" aria-label="<?= htmlspecialchars(t('bancoinfo.menu.combo'), ENT_QUOTES, 'UTF-8') ?>">
            <option value="c2"<?= $combo === 'c2' ? ' selected' : '' ?>><?= htmlspecialchars(t('bancoinfo.combo.c2'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="c3"<?= $combo === 'c3' ? ' selected' : '' ?>><?= htmlspecialchars(t('bancoinfo.combo.c3'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="c4"<?= $combo === 'c4' ? ' selected' : '' ?>><?= htmlspecialchars(t('bancoinfo.combo.c4'), ENT_QUOTES, 'UTF-8') ?></option>
          </select>
          <select id="bancoInfoScope" aria-label="<?= htmlspecialchars(t('bancoinfo.menu.scope'), ENT_QUOTES, 'UTF-8') ?>">
            <option value="dernier"<?= $scope === 'dernier' ? ' selected' : '' ?>><?= htmlspecialchars(t('bancoinfo.scope.dernier'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="tous"<?= $scope === 'tous' ? ' selected' : '' ?>><?= htmlspecialchars(t('bancoinfo.scope.tous'), ENT_QUOTES, 'UTF-8') ?></option>
          </select>
        </div>
      </div>

      <div class="banco-info-block banco-info-block--stats">
        <?php if (!$combTableExists): ?>
          <p class="no-data"><?= htmlspecialchars(t('bancoinfo.table.missing'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
          <p><?= htmlspecialchars($line1Text, ENT_QUOTES, 'UTF-8') ?></p>
          <p><?= htmlspecialchars($line2Text, ENT_QUOTES, 'UTF-8') ?></p>
          <p><?= htmlspecialchars($line3Text, ENT_QUOTES, 'UTF-8') ?></p>
          <p><?= htmlspecialchars($line4Text, ENT_QUOTES, 'UTF-8') ?></p>
          <p><?= htmlspecialchars($line5Text, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="banco-info-right">
      <div class="banco-info-table-wrap">
        <p id="bancoInfoFilterMessage" class="banco-filter-message"<?= $showFilterMessage ? '' : ' hidden' ?>>
          <?= htmlspecialchars(t('bancoinfo.filter.prompt'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if ($combTableExists): ?>
        <table class="interactive-table banco-info-table">
          <thead>
            <tr>
              <th><?= htmlspecialchars(t('bancoinfo.col.tirage'), ENT_QUOTES, 'UTF-8') ?></th>
              <?php foreach ($fields as $_): ?>
                <th><?= htmlspecialchars(t('bancoinfo.col.num'), ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
              <th><?= t('bancoinfo.col.days') ?></th>
              <th><?= t('bancoinfo.col.days2') ?></th>
              <th><?= t('bancoinfo.col.fois') ?></th>
              <th><?= t('bancoinfo.col.max') ?></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars((string) $r['Tirage'], ENT_QUOTES, 'UTF-8') ?></td>
              <?php foreach ($fields as $f): ?>
                <td><span class="circle"><?= htmlspecialchars((string) $r[$f], ENT_QUOTES, 'UTF-8') ?></span></td>
              <?php endforeach; ?>
              <td><?= htmlspecialchars((string) $r['days'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $r['days2'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $r['fois'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string) $r['max'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
