<?php
// SPDX-License-Identifier: AGPL-3.0-only
?>
<main class="pg-caption-<?php p($_['captionMode']); ?> mpi-gallery<?php if ($_['showHeader']): ?> mpi-native<?php endif; ?>" aria-labelledby="mpi-title">
    <header class="mpi-heading<?php if($_['embed']): ?> pg-visually-hidden<?php endif; ?>">
        <h1 id="mpi-title"><?php p($_['title']); ?></h1>
        <?php if ($_['description'] !== ''): ?><p class="mpi-intro"><?php p($_['description']); ?></p><?php endif; ?>
    </header>
    <?php if ($_['years']!==[] || $_['locations']!==[]): ?>
    <form class="pg-filters" method="get" action="<?php p($_['filterAction']); ?>" aria-label="Alben filtern">
        <?php if($_['embed']): ?><input type="hidden" name="embed" value="1"><?php endif; ?>
        <label>Jahr<select name="year" aria-label="Jahr"><option value="all">Alle</option>
        <?php foreach($_['years'] as $choice): ?><option value="<?php p($choice['value']); ?>" <?php if($_['year']===$choice['value']): ?>selected<?php endif; ?>><?php p($choice['label']); ?></option><?php endforeach; ?></select></label>
        <label>Ort<select name="location" aria-label="Ort"><option value="">Alle</option>
        <?php foreach($_['locations'] as $place): ?><option value="<?php p($place); ?>" <?php if($_['selectedLocation']===$place): ?>selected<?php endif; ?>><?php p($place); ?></option><?php endforeach; ?></select></label>
        <button type="submit">Filter</button>
        <a class="pg-clear" href="<?php p($_['clearUrl']); ?>" aria-label="Filter zurücksetzen" title="zurücksetzen"><span class="pg-clear-label">zurücksetzen</span><svg class="pg-clear-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/></svg></a>
    </form>
    <?php endif; ?>
    <?php if ($_['message'] !== ''): ?>
        <p class="mpi-empty" role="status"><?php p($_['message']); ?></p>
    <?php endif; ?>
    <?php foreach ($_['groups'] as $group): ?>
    <section class="mpi-year-section"<?php if ($_['groupYears']): ?> aria-labelledby="mpi-year-<?php p($group['year']); ?>"<?php endif; ?>>
        <?php if ($_['groupYears']): ?><h2 class="mpi-year-title" id="mpi-year-<?php p($group['year']); ?>"><?php p($group['label']); ?></h2><?php endif; ?>
        <div class="mpi-grid">
        <?php foreach ($group['cards'] as $card): ?>
        <a class="mpi-card" href="<?php p($card['url']); ?>">
            <div class="mpi-image">
                <span class="mpi-placeholder" aria-hidden="true"><svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="7" width="38" height="34" rx="4"/><circle cx="16" cy="18" r="4"/><path d="m6 34 10-10 9 9 7-7 10 10"/></svg></span>
                <?php if ($card['preview'] !== null): ?>
                <img data-reveal src="<?php p($card['preview']); ?>" alt="" width="640" height="640" loading="lazy" decoding="async"<?php if ($card['isVideo'] ?? false): ?> data-video-stream="<?php p($card['videoStream']); ?>"<?php endif; ?> referrerpolicy="no-referrer">
                <?php endif; ?>
                <?php if ($card['isVideo'] ?? false): ?><span class="mpi-video-badge">▶ Video</span><?php endif; ?>
            </div>
            <div class="mpi-card-body">
                <?php if ($_['groupYears']): ?><h3 class="mpi-album-title"><?php p($card['name']); ?></h3><?php else: ?><h2 class="mpi-album-title"><?php p($card['name']); ?></h2><?php endif; ?>
                <p class="mpi-metadata"><?php if ($card['location'] !== ''): ?><span class="mpi-location"><?php p($card['location']); ?></span><span class="mpi-separator">, </span><?php endif; ?><span class="mpi-file-count"><?php p($card['count']); ?> <?php p($card['count'] === 1 ? 'Datei' : 'Dateien'); ?></span></p>
            </div>
        </a>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
    <?php if ($_['pages'] > 1): ?>
    <nav class="mpi-pagination" aria-label="Galerieseiten">
        <?php if ($_['previous']): ?><a href="<?php p($_['previous']); ?>" rel="prev">← Zurück</a><?php endif; ?>
        <span>Seite <?php p($_['page']); ?> von <?php p($_['pages']); ?></span>
        <?php if ($_['next']): ?><a href="<?php p($_['next']); ?>" rel="next">Weiter →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php if ($_['footerHtml'] !== ''): ?><footer class="mpi-custom-footer"><?php print_unescaped($_['footerHtml']); ?></footer><?php endif; ?>
</main>
