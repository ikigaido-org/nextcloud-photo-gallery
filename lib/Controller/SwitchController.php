<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;
use OCA\PhotoGallery\Service\AccountSwitch;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\UseSession;
final class SwitchController extends Controller {
    public function __construct(IRequest $request, private AccountSwitch $switch, private IURLGenerator $urls) { parent::__construct('photo_gallery',$request); }
    private function response(array $data, int $status=200): JSONResponse {
        $r = new JSONResponse($data,$status); $r->addHeader('Cache-Control','no-store'); return $r;
    }
    // Session mutations require normal Nextcloud CSRF validation. No PublicPage or NoCSRFRequired.
    #[NoAdminRequired] #[UseSession]
    public function start(string $expectedUser='', string $expectedTarget=''): JSONResponse {
        try {
            $this->switch->start($expectedUser,$expectedTarget);
            return $this->response(['redirect'=>$this->urls->linkToRoute('photos.page.index')]);
        } catch (\DomainException|\InvalidArgumentException $e) { return $this->response(['message'=>$e->getMessage()],403); }
    }
    #[NoAdminRequired] #[UseSession]
    public function finish(string $expectedTarget=''): JSONResponse {
        try {
            $this->switch->finish($expectedTarget);
            return $this->response(['redirect'=>$this->urls->linkToRoute('files.view.index')]);
        } catch (\DomainException $e) { return $this->response(['message'=>$e->getMessage()],403); }
    }
}
