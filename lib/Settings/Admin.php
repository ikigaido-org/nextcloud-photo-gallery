<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Settings;

use OCA\PhotoGallery\AppInfo\Application;
use OCA\PhotoGallery\Service\Settings;
use OCP\Settings\ISettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\App\IAppManager;

final class Admin implements ISettings {
    public function __construct(private Settings $settings, private IURLGenerator $urls, private IAppManager $apps) {}
    public function getForm(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'admin', [
            'settings' => $this->settings->get(),
            'saveUrl' => $this->urls->linkToRoute(Application::APP_ID . '.admin.save'),
            'publicUrl' => $this->urls->linkToRouteAbsolute(Application::APP_ID . '.page.index'),
            'ready' => $this->apps->isEnabledForAnyone('photos'),
        ]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 10; }
}
