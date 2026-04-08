<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$astroCount = 0;
$chk = $astroConn->query("SHOW TABLES LIKE 'Astro'");
if ($chk && $chk->num_rows > 0) {
    $countRes = $astroConn->query('SELECT COUNT(*) AS total FROM Astro');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $astroCount = (int) $row['total'];
    }
}
$astroConn->close();
?>
<div id="astro-meta" data-count="<?= (int) $astroCount ?>"></div>

<div class="astro-layout astro-layout--info">
  <p class="no-data" style="margin: 1rem 0;"><?= htmlspecialchars(t('astroinfo.stub'), ENT_QUOTES, 'UTF-8') ?></p>

  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="info-row">
      <div class="info-text"><?= htmlspecialchars(t('astroinfo.placeholder'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>
</div>
