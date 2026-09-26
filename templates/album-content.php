<?php // SPDX-License-Identifier: AGPL-3.0-only ?>
<main class="mpi-gallery<?php if ($_['showHeader']): ?> mpi-native<?php endif; ?>" id="pg-album" data-api="<?php p($_['apiUrl']); ?>" data-placeholder="<?php p($_['assetBase'].'img/photo-placeholder.svg'); ?>" data-available="<?php p($_['available']?'1':'0'); ?>">
<a class="pg-back" href="<?php p($_['backUrl']); ?>">← Zur Albumübersicht</a>
<header class="mpi-heading<?php if($_['embed']): ?> pg-visually-hidden<?php endif; ?>"><h1 id="mpi-title"><?php p($_['title']); ?></h1>
<?php if ($_['location']!=='' || $_['eventLabel']!==''): ?><p class="mpi-intro pg-album-meta">
<?php if ($_['location']!==''): ?><span><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><?php p($_['location']); ?></span><?php endif; ?>
<?php if ($_['location']!=='' && $_['eventLabel']!==''): ?><span aria-hidden="true">·</span><?php endif; ?>
<?php if ($_['eventLabel']!==''): ?><span><?php p($_['eventLabel']); ?></span><?php endif; ?></p><?php endif; ?></header>
<p id="pg-status" role="status"><?php p($_['available']?'Fotos und Videos werden geladen …':'Dieses Album ist nicht öffentlich verfügbar.'); ?></p>
<div class="pg-media-grid" id="pg-grid" aria-label="Fotos und Videos"></div>
<div id="pg-sentinel" aria-hidden="true"></div>
<button type="button" class="pg-load" id="pg-more" hidden>Erneut versuchen</button>
<noscript>Zum Anzeigen dieses Albums bitte JavaScript aktivieren.</noscript>
<?php if ($_['footerHtml']!==''): ?><footer class="mpi-custom-footer"><?php print_unescaped($_['footerHtml']); ?></footer><?php endif; ?>
</main>
