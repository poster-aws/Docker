<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';

$allowedGridLimits = [100, 365];
$gridLimit = isset($_GET['grid_limit']) && in_array((int) $_GET['grid_limit'], $allowedGridLimits, true)
    ? (int) $_GET['grid_limit']
    : 100;

$hasStats = false;
$chkStats = $astroConn->query("SHOW TABLES LIKE 'Astro_stats'");
if ($chkStats && $chkStats->num_rows > 0) {
    $hasStats = true;
}

$astroCount = null;
$astroCombOut = 0;
$tirages = [];
$jourLastSeen = array_fill(1, 31, '');
$anneeLastSeen = array_fill(0, 100, '');

// Сначала берём готовые значения из Astro_info (если заполнено процедурами).
$infoTable = $astroConn->query("SHOW TABLES LIKE 'Astro_info'");
if ($infoTable && $infoTable->num_rows > 0) {
    $infoRes = $astroConn->query('SELECT Tirages, Comb_out FROM Astro_info LIMIT 1');
    if ($infoRes && $infoRow = $infoRes->fetch_assoc()) {
        $astroCount = (int) $infoRow['Tirages'];
        $astroCombOut = (int) $infoRow['Comb_out'];
    }
}

if ($hasStats) {
    if ($astroCount === null) {
        $countRes = $astroConn->query('SELECT COUNT(*) AS total FROM Astro_stats');
        if ($countRes && $row = $countRes->fetch_assoc()) {
            $astroCount = (int) $row['total'];
        }
    }
    $sqlGrid = 'SELECT Tirage, jour, mois, annee, signe FROM Astro_stats ORDER BY Tirage DESC LIMIT ' . (int) $gridLimit;
    $resGrid = $astroConn->query($sqlGrid);
    if ($resGrid && $resGrid->num_rows > 0) {
        while ($r = $resGrid->fetch_assoc()) {
            $tirages[] = [
                'Tirage' => $r['Tirage'],
                'jour'   => (int) $r['jour'],
                'mois'   => (int) $r['mois'],
                'annee'  => (int) $r['annee'],
                'signe'  => (int) $r['signe'],
            ];
        }
    }

    $resJourLast = $astroConn->query('SELECT jour, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY jour');
    if ($resJourLast && $resJourLast->num_rows > 0) {
        while ($r = $resJourLast->fetch_assoc()) {
            $j = (int) $r['jour'];
            if ($j >= 1 && $j <= 31) {
                $jourLastSeen[$j] = (string) ($r['last_tirage'] ?? '');
            }
        }
    }

    $resAnneeLast = $astroConn->query('SELECT annee, MAX(Tirage) AS last_tirage FROM Astro_stats GROUP BY annee');
    if ($resAnneeLast && $resAnneeLast->num_rows > 0) {
        while ($r = $resAnneeLast->fetch_assoc()) {
            $a = (int) $r['annee'];
            if ($a >= 0 && $a <= 99) {
                $anneeLastSeen[$a] = (string) ($r['last_tirage'] ?? '');
            }
        }
    }
} else {
    if ($astroCount === null) {
        $chk = $astroConn->query("SHOW TABLES LIKE 'Astro'");
        if ($chk && $chk->num_rows > 0) {
            $countRes = $astroConn->query('SELECT COUNT(*) AS total FROM Astro');
            if ($countRes && $row = $countRes->fetch_assoc()) {
                $astroCount = (int) $row['total'];
            }
        }
    }
}

$astroConn->close();

/** Symboles zodiaque (même ordre que la page principale Astro) — affichage dans les cellules « hit » */
$signeSymboles = [
    1 => "\u{2648}", 2 => "\u{2649}", 3 => "\u{264A}", 4 => "\u{264B}", 5 => "\u{264C}", 6 => "\u{264D}",
    7 => "\u{264E}", 8 => "\u{264F}", 9 => "\u{2650}", 10 => "\u{2651}", 11 => "\u{2652}", 12 => "\u{2653}",
];

$viewMois = ($astroView !== 'jour');

/** Σ pour lignes mois 1..12 */
$sumsMois = array_fill(1, 13, 0);
$sumsSigne = array_fill(1, 13, 0);
$sumsJour = array_fill(1, 32, 0);
$sumsAnnee = array_fill(0, 100, 0);

foreach ($tirages as $t) {
    $m = $t['mois'];
    if ($m >= 1 && $m <= 12) {
        $sumsMois[$m]++;
    }
    $s = $t['signe'];
    if ($s >= 1 && $s <= 12) {
        $sumsSigne[$s]++;
    }
    $j = $t['jour'];
    if ($j >= 1 && $j <= 31) {
        $sumsJour[$j]++;
    }
    $a = $t['annee'];
    if ($a >= 0 && $a <= 99) {
        $sumsAnnee[$a]++;
    }
}

$today = new DateTime('today');

/**
 * @return array{date: string, daysAgo: string}
 */
function astro_last_seen_stats(string $date, DateTime $today): array
{
    if ($date === '') {
        return ['date' => '', 'daysAgo' => ''];
    }
    try {
        $d = new DateTime($date);
        return ['date' => $date, 'daysAgo' => (string) $today->diff($d)->days];
    } catch (Throwable $e) {
        return ['date' => $date, 'daysAgo' => ''];
    }
}

/**
 * @param array<int, array{value: string, count: int, date: string, daysAgo: string}> $items
 * @return array{top: array<int, array{value: string, count: int, date: string, daysAgo: string}>, bottom: array<int, array{value: string, count: int, date: string, daysAgo: string}>}
 */
function astro_ranked_top_bottom(array $items, int $size = 10): array
{
    $top = $items;
    usort($top, static function (array $a, array $b): int {
        if ($a['count'] === $b['count']) {
            return strcmp($a['value'], $b['value']);
        }
        return $b['count'] <=> $a['count'];
    });

    $bottom = $items;
    usort($bottom, static function (array $a, array $b): int {
        if ($a['count'] === $b['count']) {
            return strcmp($a['value'], $b['value']);
        }
        return $a['count'] <=> $b['count'];
    });

    return [
        'top' => array_slice($top, 0, $size),
        'bottom' => array_slice($bottom, 0, $size),
    ];
}

$jourItems = [];
for ($j = 1; $j <= 31; $j++) {
    $last = astro_last_seen_stats($jourLastSeen[$j], $today);
    $jourItems[] = [
        'value' => (string) $j,
        'count' => (int) $sumsJour[$j],
        'date' => $last['date'],
        'daysAgo' => $last['daysAgo'],
    ];
}

$anneeItems = [];
for ($a = 0; $a <= 99; $a++) {
    $label = sprintf('%02d', $a);
    $last = astro_last_seen_stats($anneeLastSeen[$a], $today);
    $anneeItems[] = [
        'value' => $label,
        'count' => (int) $sumsAnnee[$a],
        'date' => $last['date'],
        'daysAgo' => $last['daysAgo'],
    ];
}

$jourRanks = astro_ranked_top_bottom($jourItems, 10);
$anneeRanks = astro_ranked_top_bottom($anneeItems, 10);
$jourMax = max(1, ...$sumsJour);
$anneeMax = max(1, ...$sumsAnnee);

?>
<div class="astro-layout astro-layout--info astroinfo-layout">
  <div id="astro-meta" data-count="<?= (int) $astroCount ?>" data-view="<?= htmlspecialchars($astroView, ENT_QUOTES, 'UTF-8') ?>"></div>

  <?php if (!$hasStats): ?>
    <p class="no-data" style="margin: 1rem 0;"><?= htmlspecialchars(t('astroinfo.no_stats_table'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php elseif (empty($tirages)): ?>
    <p class="no-data" style="margin: 1rem 0;"><?= htmlspecialchars(t('astroinfo.no_data'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <?php if ($viewMois): ?>
      <div class="table-wrapper astroinfo-grid astroinfo-grid--mois">
        <table class="digit-grid">
          <thead>
            <tr>
              <th>Σ</th>
              <th>#</th>
              <?php foreach ($tirages as $t): ?>
                <th><?= htmlspecialchars((string) $t['Tirage'], ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php for ($mois = 1; $mois <= 12; $mois++):
                $moisLabel = trim(t('astro.mois.' . $mois));
                ?>
              <tr>
                <td>&nbsp;<?= (int) $sumsMois[$mois] ?>x&nbsp;</td>
                <td><?= htmlspecialchars($moisLabel, ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($tirages as $t):
                    $hit = ($t['mois'] === $mois);
                    ?>
                  <td class="<?= $hit ? 'hit' : '' ?>"><?= $hit ? (int) $mois : '' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
      <div class="table-wrapper astroinfo-grid astroinfo-grid--signe">
        <table class="digit-grid">
          <thead>
            <tr>
              <th>Σ</th>
              <th>#</th>
              <?php foreach ($tirages as $t): ?>
                <th><?= htmlspecialchars((string) $t['Tirage'], ENT_QUOTES, 'UTF-8') ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php for ($sig = 1; $sig <= 12; $sig++):
                $signeLabel = trim(t('astro.signe.' . $sig));
                ?>
              <tr>
                <td>&nbsp;<?= (int) $sumsSigne[$sig] ?>x&nbsp;</td>
                <td><?= htmlspecialchars($signeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($tirages as $t):
                    $hit = ($t['signe'] === $sig);
                    ?>
                  <td class="<?= $hit ? 'hit hit--sign' : '' ?>"><?= $hit ? ($signeSymboles[$sig] ?? '') : '' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <section class="astro-analytics">
        <h3><?= htmlspecialchars(t('astro.col.jour'), ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="astro-heatmap">
          <?php foreach ($jourItems as $item):
              $heat = $item['count'] / $jourMax;
              ?>
            <div class="astro-heatcell" style="--heat: <?= number_format($heat, 3, '.', '') ?>">
              <span class="v"><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="c"><?= (int) $item['count'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="astro-metrics-grid">
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astroinfo.analytics.top10'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.fois'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php foreach ($jourRanks['top'] as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['count'] ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astroinfo.analytics.bottom10'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.fois'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php foreach ($jourRanks['bottom'] as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['count'] ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astro.col.jour'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.days'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(trim((string) preg_replace('/<br\\s*\\/?\\s*>/i', ' ', t('q2.col.last_draw'))), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php
                $jourByDelay = $jourItems;
                usort($jourByDelay, static function (array $a, array $b): int {
                    $ad = ($a['daysAgo'] === '') ? -1 : (int) $a['daysAgo'];
                    $bd = ($b['daysAgo'] === '') ? -1 : (int) $b['daysAgo'];
                    return $bd <=> $ad;
                });
                foreach (array_slice($jourByDelay, 0, 10) as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['daysAgo'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="astro-analytics">
        <h3><?= htmlspecialchars(t('astro.col.annee'), ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="astro-heatmap astro-heatmap--years">
          <?php foreach ($anneeItems as $item):
              $heat = $item['count'] / $anneeMax;
              ?>
            <div class="astro-heatcell" style="--heat: <?= number_format($heat, 3, '.', '') ?>">
              <span class="v"><?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?></span>
              <span class="c"><?= (int) $item['count'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="astro-metrics-grid">
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astroinfo.analytics.top10'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.fois'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php foreach ($anneeRanks['top'] as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['count'] ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astroinfo.analytics.bottom10'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.fois'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php foreach ($anneeRanks['bottom'] as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $row['count'] ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="number-stats-table">
            <table class="interactive-table">
              <thead><tr><th><?= htmlspecialchars(t('astro.col.annee'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(t('astro.col.days'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(trim((string) preg_replace('/<br\\s*\\/?\\s*>/i', ' ', t('q2.col.last_draw'))), ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
              <tbody>
                <?php
                $anneeByDelay = $anneeItems;
                usort($anneeByDelay, static function (array $a, array $b): int {
                    $ad = ($a['daysAgo'] === '') ? -1 : (int) $a['daysAgo'];
                    $bd = ($b['daysAgo'] === '') ? -1 : (int) $b['daysAgo'];
                    return $bd <=> $ad;
                });
                foreach (array_slice($anneeByDelay, 0, 10) as $row): ?>
                  <tr><td><?= htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['daysAgo'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>

  <div class="filter-form">
    <?= htmlspecialchars(t('q2info.latest'), ENT_QUOTES, 'UTF-8') ?>
    <select id="astroInfoGridLimit">
      <?php foreach ($allowedGridLimits as $opt): ?>
        <option value="<?= $opt ?>" <?= ($gridLimit === $opt) ? 'selected' : '' ?>><?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    <?= htmlspecialchars(t('q2info.draws_suffix'), ENT_QUOTES, 'UTF-8') ?>
  </div>

  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="info-row info-row--schedule">
      <span class="info-sign info-sign--cost" aria-hidden="true">$</span>
      <div class="info-text"><?= t('astroinfo.info.cost') ?></div>
    </div>
    <div class="info-row info-row--schedule">
      <span class="info-sign info-sign--sum" aria-hidden="true">&#8721;</span>
      <div class="info-text"><?= sprintf(t('astroinfo.info.unique_combos'), (int) $astroCombOut) ?></div>
    </div>
    <div class="info-row">
      <div class="info-text"><?= t('astroinfo.info.all_combinations') ?></div>
    </div>
    <div class="info-row">
      <div class="info-text">
        <details>
          <summary><?= t('astroinfo.info.types2.summary') ?></summary>
          <div><?= t('astroinfo.info.types2.1') ?></div>
          <div><?= t('astroinfo.info.types2.2') ?></div>
          <div><?= t('astroinfo.info.types2.3') ?></div>
          <div><?= t('astroinfo.info.types2.4') ?></div>
          <div><?= t('astroinfo.info.types2.5') ?></div>
          <div><?= t('astroinfo.info.types2.6') ?></div>
        </details>
      </div>
    </div>
    <div class="info-row">
      <div class="info-text">
        <details>
          <summary><?= t('astroinfo.info.types3.summary') ?></summary>
          <div><?= t('astroinfo.info.types3.1') ?></div>
          <div><?= t('astroinfo.info.types3.2') ?></div>
          <div><?= t('astroinfo.info.types3.3') ?></div>
          <div><?= t('astroinfo.info.types3.4') ?></div>
        </details>
      </div>
    </div>
  </div>
</div>
