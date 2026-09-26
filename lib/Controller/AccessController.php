<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;
use OCA\PhotoGallery\Service\ToolAccess;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
final class AccessController extends Controller {
    public function __construct(IRequest $request, private ToolAccess $access) { parent::__construct('photo_gallery', $request); }
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function check(): JSONResponse {
        $allowed = $this->access->allowed();
        $response = new JSONResponse(['allowed'=>$allowed], $allowed ? 200 : 403);
        $response->addHeader('Cache-Control', 'no-store');
        return $response;
    }
}
