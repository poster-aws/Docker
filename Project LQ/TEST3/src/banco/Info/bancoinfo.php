<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../i18n.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$bancoCount = 0;
$tableExists = $bancoConn->query("SHOW TABLES LIKE 'banco'");
if ($tableExists && $tableExists->num_rows > 0) {
    $countRes = $bancoConn->query('SELECT COUNT(*) AS total FROM banco');
    if ($countRes && $row = $countRes->fetch_assoc()) {
        $bancoCount = (int) $row['total'];
    }
}
$bancoConn->close();
?>
<div id="banco-meta" data-count="<?= (int) $bancoCount ?>" data-header-sub-fr="<?= htmlspecialchars(t_for_lang('banco.header.sub', 'fr'), ENT_QUOTES, 'UTF-8') ?>" data-header-sub-en="<?= htmlspecialchars(t_for_lang('banco.header.sub', 'en'), ENT_QUOTES, 'UTF-8') ?>"></div>

<div class="banco-layout banco-layout--info">
  <p class="no-data"><?= htmlspecialchars(t('bancoinfo.stub'), ENT_QUOTES, 'UTF-8') ?></p>

  <div id="infoBlock" class="info-list">
    <div class="info-row info-row--schedule">
      <span class="info-sign" aria-hidden="true">&#8505;</span>
      <div class="info-text"><?= htmlspecialchars(t('infoblock.schedule.daily'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="info-row">
      <div class="info-text"><?= htmlspecialchars(t('bancoinfo.placeholder'), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>
</div>
