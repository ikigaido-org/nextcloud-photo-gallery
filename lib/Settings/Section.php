<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Settings;
use OCA\PhotoGallery\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

final class Section implements IIconSection {
    public function __construct(private IURLGenerator $urls) {}
    public function getID(): string { return Application::APP_ID; }
    public function getName(): string { return 'Photo Gallery'; }
    public function getPriority(): int { return 80; }
    public function getIcon(): string { return $this->urls->imagePath(Application::APP_ID, 'settings.svg'); }
}
