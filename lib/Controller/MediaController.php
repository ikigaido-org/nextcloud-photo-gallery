<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;
use OCA\PhotoGallery\Service\Settings;
use OCA\PhotoGallery\Service\Catalog;
use OCA\PhotoGallery\Service\Metadata;
use OCA\PhotoGallery\Db\AlbumRepository;
use OCA\PhotoGallery\Http\MediaResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCP\IPreview;
use OCP\IRequest;
use OCP\IUserManager;

final class MediaController extends Controller {
    public function __construct(IRequest $request, private Settings $settings, private Catalog $catalog,
        private AlbumRepository $albums, private IRootFolder $root, private IPreview $previewManager,
        private Metadata $metadataService, private IUserManager $users) { parent::__construct('photo_gallery',$request); }
    private function file(int $id, int $fileId): ?File {
        $album=$this->catalog->resolve($this->settings->get(),$id);
        if (!$album) { return null; }
        $row=$this->albums->findFile($id,$fileId);
        if (!$row) { return null; }
        $owner=$row['owner'] ?: $album['user'];
        $user=$this->users->get($owner);
        if (!$user || !$user->isEnabled()) { return null; }
        foreach ($this->root->getUserFolder($owner)->getById($fileId) as $file) {
            if ($file instanceof File && (str_starts_with($file->getMimeType(),'image/')||str_starts_with($file->getMimeType(),'video/'))) { return $file; }
        }
        return null;
    }
    private function missing(): Response { return $this->headers(new JSONResponse(['message'=>'Medium nicht verfügbar.'],404)); }
    private function headers(Response $r): Response {
        $r->addHeader('Cache-Control','no-store, max-age=0'); $r->addHeader('Referrer-Policy','no-referrer');
        $r->addHeader('X-Content-Type-Options','nosniff');return $r;
    }
    #[PublicPage] #[NoCSRFRequired]
    public function preview(int $id, int $fileId, bool $large=false, bool $fit=false): Response {
        try {
            $file=$this->file($id,$fileId); if (!$file) { return $this->missing(); }
            try{$image=$this->previewManager->getPreview($file,$large?2048:640,$large?2048:640,false,IPreview::MODE_FILL);}
            catch(\Throwable $e){if($large){throw $e;}$image=$this->previewManager->getPreview($file,2048,2048,false,IPreview::MODE_FILL);}
            $r=new FileDisplayResponse($image,200,['Content-Type'=>$image->getMimeType()]);
            $r->addHeader('Content-Disposition','inline');return $this->headers($r);
        } catch (\Throwable) { return $this->missing(); }
    }
    #[PublicPage] #[NoCSRFRequired]
    public function stream(int $id, int $fileId): Response {
        try {
            $file=$this->file($id,$fileId);
            if (!$file || !str_starts_with($file->getMimeType(),'video/')) { return $this->missing(); }
            return new MediaResponse($file,$this->request->getHeader('Range'));
        } catch (\Throwable) { return $this->missing(); }
    }
    #[PublicPage] #[NoCSRFRequired]
    public function metadata(int $id, int $fileId): Response {
        try {
            $file=$this->file($id,$fileId); if (!$file) { return $this->missing(); }
            return $this->headers(new JSONResponse(['fields'=>$this->metadataService->read($file,$this->settings->get()['metadataFields'])]));
        } catch (\Throwable) { return $this->missing(); }
    }
}
