<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Repair;
use OCP\IConfig;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;
/** Called by Nextcloud when disabling/uninstalling the app. */
final class EndSwitches implements IRepairStep {
    public function __construct(private IConfig $config) {}
    public function getName(): string { return 'Invalidate gallery account switches'; }
    public function run(IOutput $output): void {
        // Persistent sessions still belong to the original account. This generation
        // prevents an old delegation from resuming after the app is enabled again.
        $this->config->setAppValue('photo_gallery','switchGeneration',bin2hex(random_bytes(16)));
    }
}
