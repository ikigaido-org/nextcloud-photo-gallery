<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;
use OCA\PhotoGallery\Service\Feed;
use OCA\PhotoGallery\Service\Settings;
use OCA\PhotoGallery\Http\FeedResponse;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use Psr\Log\LoggerInterface;
final class FeedController extends Controller {
    public function __construct(IRequest $request,private Settings $settings,private Feed $feed,private LoggerInterface $logger){parent::__construct('photo_gallery',$request);}
    #[PublicPage]
    #[NoCSRFRequired]
    public function rss(): FeedResponse {return $this->respond('rss');}
    #[PublicPage]
    #[NoCSRFRequired]
    public function atom(): FeedResponse {return $this->respond('atom');}
    private function respond(string $format): FeedResponse {
        $s=$this->settings->get();if(!$s['enabled']){return new FeedResponse('Galerie nicht veröffentlicht.','text/plain',404);}
        try{return new FeedResponse($this->feed->render($s,$format),$format==='rss'?'application/rss+xml':'application/atom+xml');}
        catch(\Throwable $e){$this->logger->error('Gallery feed unavailable',['exception'=>$e]);return new FeedResponse('Feed vorübergehend nicht verfügbar.','text/plain',503);}
    }
}
