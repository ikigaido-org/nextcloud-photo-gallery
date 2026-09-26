<?php
// SPDX-License-Identifier: AGPL-3.0-only
/** @var array $_ */
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?php p($_['seo']['title']); ?></title>
<?php require __DIR__.'/seo.php'; ?>
    <link rel="icon" href="<?php p($_['iconUrl']); ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?php p($_['cssUrl']); ?>">
    <?php if ($_['appearanceCss'] !== ''): ?><style><?php print_unescaped($_['appearanceCss']); ?></style><?php endif; ?>
    <script src="<?php p($_['videoPreviewJs']); ?>" nonce="<?php p($_['scriptNonce']); ?>" defer></script>
<script src="<?php p($_['jsUrl']); ?>" nonce="<?php p($_['scriptNonce']); ?>" defer></script>
</head>
<body>
<?php require __DIR__ . '/gallery.php'; ?>
<noscript><style>.mpi-image img[data-reveal]{opacity:1}</style></noscript>
</body>
</html>
