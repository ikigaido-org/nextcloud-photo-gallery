<?php
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
?>
<link rel="stylesheet" href="<?php p($_['cssUrl']); ?>">
<?php if ($_['appearanceCss'] !== ''): ?><style><?php print_unescaped($_['appearanceCss']); ?></style><?php endif; ?>
<script src="<?php p($_['videoPreviewJs']); ?>" nonce="<?php p($_['scriptNonce']); ?>" defer></script>
<script src="<?php p($_['jsUrl']); ?>" nonce="<?php p($_['scriptNonce']); ?>" defer></script>
<?php require __DIR__ . '/gallery.php'; ?>

<noscript><style>.mpi-image img[data-reveal]{opacity:1}</style></noscript>
