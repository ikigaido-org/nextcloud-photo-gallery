<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;

use OCA\PhotoGallery\AppInfo\Application;
use OCA\PhotoGallery\Service\Settings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class AdminController extends Controller {
    public function __construct(IRequest $request, private Settings $settings) {
        parent::__construct(Application::APP_ID, $request);
    }

    // Default framework middleware requires an authenticated admin and valid CSRF token.
    // Deliberately no NoAdminRequired / NoCSRFRequired / PublicPage attributes.
    public function save(bool $enabled = false, string $title = '', string $description = '',
        string $owners = '', string $sort = 'folder_date',
        ?array $appearance = null, ?string $yearView = null,
        ?array $dateOptions = null, ?bool $showHeader = null, ?string $footerHtml = null, ?array $metadataFields = null, ?string $coverMatch=null, ?bool $cleanUrls=null, ?string $publicBaseUrl=null, ?bool $restrictTools=null, ?string $toolUsers=null, ?string $toolGroups=null, ?string $feedScope=null, ?int $feedFromYear=null, ?string $switchTarget=null): JSONResponse {
        try {
            $this->settings->save($enabled, $title, $description, $owners, $sort, $appearance, $yearView, $dateOptions, $showHeader, $footerHtml, $metadataFields, $coverMatch, $cleanUrls, $publicBaseUrl, $restrictTools, $toolUsers, $toolGroups, $feedScope, $feedFromYear, $switchTarget);
            return new JSONResponse(['message' => 'Einstellungen gespeichert.']);
        } catch (\InvalidArgumentException $e) {
            return new JSONResponse(['message' => $e->getMessage()], 400);
        }
    }
}
