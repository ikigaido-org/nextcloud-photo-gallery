<?php // SPDX-License-Identifier: AGPL-3.0-only
foreach (\OCA\PhotoGallery\Service\Seo::tags($_['seo']) as [$tag,$attributes]): ?>
<<?php print_unescaped($tag); ?><?php foreach($attributes as $name=>$value): ?> <?php print_unescaped($name); ?>="<?php p($value); ?>"<?php endforeach; ?>>
<?php endforeach; ?>
