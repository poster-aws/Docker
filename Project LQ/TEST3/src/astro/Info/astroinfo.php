<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$astroView = (isset($_GET['astro_view']) && $_GET['astro_view'] === 'jour') ? 'jour' : 'mois';

$allowedGridLimits = [30, 100, 365];
$gridLimit = isset($_GET['grid_limit']) && in_array((int) $_GET['grid_limit'], $allowedGridLimits, true)
    ? (int) $_GET['grid_limit']
    : 100;

$hasStats = false;
$chkStats = $astroConn->query("SHOW TABLES LIKE 'Astro_stats'");
if ($chkStats && $chkStats->num_rows > 0) {
    $hasStats = true;
}

$astroCount = 0;
$tirages = [];

if ($hasStats) {
    $countRes = $astroConn->query('SELECT COUNT(*) AS total FROM Astro_stats');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $astroCount = (int) $row['total'];
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
} else {
    $chk = $astroConn->query("SHOW TABLES LIKE 'Astro'");
    if ($chk && $chk->num_rows > 0) {
        $countRes = $astroConn->query('SELECT COUNT(*) AS total FROM Astro');
        if ($countRes && $row = $countRes->fetch_assoc()) {
            $astroCount = (int) $row['total'];
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
      <div class="table-wrapper astroinfo-grid astroinfo-grid--jour">
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
            <?php for ($jour = 1; $jour <= 31; $jour++): ?>
              <tr>
                <td>&nbsp;<?= (int) $sumsJour[$jour] ?>x&nbsp;</td>
                <td><?= $jour ?></td>
                <?php foreach ($tirages as $t):
                    $hit = ($t['jour'] === $jour);
                    ?>
                  <td class="<?= $hit ? 'hit' : '' ?>"><?= $hit ? $jour : '' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
      <div class="table-wrapper astroinfo-grid astroinfo-grid--annee">
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
            <?php for ($an = 0; $an <= 99; $an++):
                $anneeAff = sprintf('%02d', $an);
                ?>
              <tr>
                <td>&nbsp;<?= (int) $sumsAnnee[$an] ?>x&nbsp;</td>
                <td><?= htmlspecialchars($anneeAff, ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($tirages as $t):
                    $hit = ($t['annee'] === $an);
                    ?>
                  <td class="<?= $hit ? 'hit' : '' ?>"><?= $hit ? htmlspecialchars($anneeAff, ENT_QUOTES, 'UTF-8') : '' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="filter-form">
    <?= htmlspecialchars(t('q2info.last_draw_count'), ENT_QUOTES, 'UTF-8') ?>
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
  </div>
</div>
