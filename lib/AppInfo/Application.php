<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\AppInfo;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\PhotoGallery\Listener\FilesListener;
final class Application extends App implements IBootstrap {
    public const APP_ID = 'photo_gallery';
    public function __construct(array $urlParams = []) { parent::__construct(self::APP_ID, $urlParams); }
    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, FilesListener::class);
        $context->registerEventListener(\OCP\Navigation\Events\LoadAdditionalEntriesEvent::class, \OCA\PhotoGallery\Listener\AccountMenuListener::class);
    }
    public function boot(IBootContext $context): void {
        if (PHP_SAPI === 'cli') { return; }
        $context->injectFn(function (\OCA\PhotoGallery\Service\AccountSwitch $switch, \OCA\PhotoGallery\Service\SwitchUi $ui): void {
            $switch->prepare();
            $ui->load();
        });
    }
}
