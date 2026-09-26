<?php // SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only ?>
<link rel="stylesheet" href="<?php p($_['cssUrl']); ?>">
<link rel="stylesheet" href="<?php p($_['assetBase'].'vendor/lightgallery/css/lightgallery-bundle.css'); ?>">
<link rel="stylesheet" href="<?php p($_['assetBase'].'css/album.css?v=0.8.7'); ?>">
<?php if ($_['appearanceCss'] !== ''): ?><style><?php print_unescaped($_['appearanceCss']); ?></style><?php endif; ?>
<?php foreach (['vendor/lightgallery/lightgallery.umd.js','vendor/lightgallery/plugins/thumbnail/lg-thumbnail.umd.js','vendor/lightgallery/plugins/zoom/lg-zoom.umd.js','vendor/lightgallery/plugins/video/lg-video.umd.js','js/video-preview.js','js/album.js'] as $script): ?>
<script defer nonce="<?php p($_['scriptNonce']); ?>" src="<?php p($_['assetBase'].$script.'?v=0.8.7'); ?>"></script>
<?php endforeach; ?>
