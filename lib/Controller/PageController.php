<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;

use OCA\PhotoGallery\AppInfo\Application;
use OCA\PhotoGallery\Service\Catalog;
use OCA\PhotoGallery\Service\Settings;
use OCA\PhotoGallery\Service\Appearance;
use OCA\PhotoGallery\Service\Footer;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

final class PageController extends Controller {
    public function __construct(IRequest $request, private Settings $settings,
        private Catalog $catalog, private IURLGenerator $urls, private LoggerInterface $logger,
        private Appearance $appearance, private \OCA\PhotoGallery\Service\PublicUrls $publicUrls, private \OCA\PhotoGallery\Service\Feed $feed, private \OCA\PhotoGallery\Service\SwitchUi $switchUi) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    public function index(int $page = 1, string $year = '', string $location = '', bool $embed = false): TemplateResponse {
        $this->switchUi->suppress();
        $settings = $this->settings->get();
        $navigation=$embed?['embed'=>1]:[];
        $params = ['embed'=>$embed, 'title' => $settings['title'], 'description' => $settings['description'],
            'captionMode'=>$settings['appearance']['captionMode'], 'filterAction'=>$this->publicUrls->index($settings), 'clearUrl'=>$this->publicUrls->index($settings,['year'=>'all']+$navigation), 'years'=>[], 'year'=>'all', 'locations'=>[], 'selectedLocation'=>$location,
            'cards' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'message' => '',
            'previous' => null, 'next' => null, 'groups' => [],
            'showHeader' => (bool)$settings['showHeader'] && !$embed, 'footerHtml' => Footer::clean($settings['footerHtml']),
            'groupYears' => $settings['yearView'] !== 'off',
            'appearanceCss' => $this->appearance->css($settings['appearance'])];
        $status = 200;
        if (!$settings['enabled']) {
            $params['message'] = 'Diese Galerie ist noch nicht veröffentlicht.';
            $status = 404;
        } else {
            try {
                $filter = $year !== '' ? $year : ($settings['yearView'] === 'latest' ? 'latest' : 'all');
                $params = array_replace($params, $this->catalog->getPage($settings, $page, $filter, $params['groupYears'],$location,$embed));
                if ($params['total'] === 0) {
                    $params['message'] = $params['allTotal'] > 0 ? 'Für diese Filter sind keine öffentlichen Alben verfügbar.' : 'Zurzeit sind keine öffentlichen Alben verfügbar.';
                }
                $pagination = ['year' => $params['year'],'location'=>$location]+$navigation;
                if ($params['page'] > 1) {
                    $params['previous'] = $this->publicUrls->index($settings, $pagination + ['page' => $params['page'] - 1]);
                }
                if ($params['page'] < $params['pages']) {
                    $params['next'] = $this->publicUrls->index($settings, $pagination + ['page' => $params['page'] + 1]);
                }
            } catch (\InvalidArgumentException $e) {
                $params['message'] = 'Ungültige Filterauswahl.';
                $status = 400;
            } catch (\Throwable $e) {
                // No tokens, account identifiers or exception detail in the public response.
                $this->logger->error('Public album catalogue could not be loaded', ['app' => Application::APP_ID, 'exception' => $e]);
                $params['message'] = 'Die Galerie ist vorübergehend nicht verfügbar.';
                $status = 503;
            }
        }
        // Use a standalone document by default, optionally the native public wrapper.
        // Generate all asset URLs centrally so custom app install paths still work.
        $params['cssUrl'] = $this->urls->linkTo(Application::APP_ID, 'css/gallery.css') . '?v=0.8.7';
        $params['videoPreviewJs'] = $this->urls->linkTo(Application::APP_ID, 'js/video-preview.js') . '?v=0.8.7';
        $params['jsUrl'] = $this->urls->linkTo(Application::APP_ID, 'js/gallery.js') . '?v=0.8.7';
        $params['iconUrl'] = $this->urls->linkTo(Application::APP_ID, 'img/gallery.svg');
        $params['scriptNonce'] = base64_encode(random_bytes(24));
        $canonical=$this->publicUrls->absolute($settings,$this->publicUrls->index($settings,['year'=>$params['year'],'location'=>$params['selectedLocation'],'page'=>$params['page']]));
        $params['feedLinks']=$status===200?$this->feed->links($settings):[];
        $params['seo']=$this->seo($settings['title'].($params['page']>1?' – Seite '.$params['page']:''),$settings['description'],$canonical,'',$settings['title'],$embed,$status);
        $params['seo']['feeds']=$params['feedLinks'];
        if ($params['showHeader']) {
            $this->nativeHead($params['seo']);
            $response = new PublicTemplateResponse(Application::APP_ID, 'public-index', $params, $status);
            $response->setHeaderTitle($settings['title']);
            $response->setFooterVisible(false);
        } else {
            $response = new TemplateResponse(Application::APP_ID, 'index', $params, TemplateResponse::RENDER_AS_BLANK, $status);
        }
        $policy = new ContentSecurityPolicy();
        $fontOrigin = $this->appearance->fontOrigin($settings['appearance']);
        if ($fontOrigin !== '') { $policy->addAllowedFontDomain($fontOrigin); }
        // An additional unpredictable nonce survives merging with the core CSP nonce.
        $policy->addAllowedScriptDomain("'nonce-" . $params['scriptNonce'] . "'");
        $response->setContentSecurityPolicy($policy);
        $response->addHeader('Cache-Control', 'no-store, max-age=0');
        $response->addHeader('Referrer-Policy', 'no-referrer');
        $response->addHeader('X-Robots-Tag',$params['seo']['robots']);
        return $response;
    }
    #[PublicPage]
    #[NoCSRFRequired]
    public function namedAlbum(string $slug, string $year='', int $page=1, string $location='', bool $embed=false): TemplateResponse {
        $album=null;
        try{$album=$this->catalog->resolveSlug($this->settings->get(),$slug);}catch(\Throwable $e){$this->logger->error('Named album unavailable',['exception'=>$e]);}
        return $this->album((int)($album['album_id']??0),$year,$page,$location,$embed);
    }
    #[PublicPage]
    #[NoCSRFRequired]
    public function album(int $id, string $year = '', int $page = 1, string $location = '', bool $embed = false): TemplateResponse {
        $this->switchUi->suppress();
        $s=$this->settings->get(); $album=null; $event=['date'=>null,'year'=>null];
        try { $album=$this->catalog->resolve($s,$id); if($album){$event=$this->catalog->eventAssignment($s,$album);} } catch (\Throwable $e) { $this->logger->error('Album unavailable',['exception'=>$e]); }
        if (!in_array($year,['','all','latest','undated'],true) && !preg_match('/^(19|20)\d{2}$/D',$year)) { $year=''; }
        $params=['embed'=>$embed, 'title'=>$album ? $album['name'] : 'Album nicht verfügbar', 'location'=>$album['location'] ?? '',
            'eventLabel'=>!empty($event['date']) ? (new \DateTimeImmutable($event['date']))->format('d.m.Y') : ($event['year'] ?? ''),
            'available'=>$album!==null, 'showHeader'=>(bool)$s['showHeader'] && !$embed, 'footerHtml'=>Footer::clean($s['footerHtml']),
            'appearanceCss'=>$this->appearance->css($s['appearance']),
            'backUrl'=>$this->publicUrls->index($s,['year'=>$year,'page'=>max(1,$page),'location'=>mb_substr($location,0,200)]+($embed?['embed'=>1]:[])),
            'apiUrl'=>$this->urls->linkToRoute('photo_gallery.api.album',['id'=>$id]),
            'assetBase'=>$this->urls->linkTo('photo_gallery',''),
            'cssUrl'=>$this->urls->linkTo('photo_gallery','css/gallery.css').'?v=0.8.7',
            'scriptNonce'=>base64_encode(random_bytes(24))];
        $sharing=[];
        if($album){try{$sharing=$this->catalog->sharing($s,$id);}catch(\Throwable $e){$this->logger->error('Album sharing metadata unavailable',['exception'=>$e]);}}
        $description=$album?implode(' · ',array_filter([$params['title'],$params['location'],$params['eventLabel'],$s['description']])):'';
        $params['seo']=$this->seo($params['title'].' – '.$s['title'],$description,$sharing['url']??'',$sharing['image']??'',$s['title'],$embed,$album?200:404);
        if ($params['showHeader']) {
            $this->nativeHead($params['seo']);
            $r=new PublicTemplateResponse('photo_gallery','album-public',$params,$album?200:404);
            $r->setHeaderTitle($s['title']); $r->setFooterVisible(false);
        } else { $r=new TemplateResponse('photo_gallery','album',$params,TemplateResponse::RENDER_AS_BLANK,$album?200:404); }
        $policy=new ContentSecurityPolicy();$policy->addAllowedScriptDomain("'nonce-".$params['scriptNonce']."'");
        $fontOrigin = $this->appearance->fontOrigin($s['appearance']);
        if ($fontOrigin !== '') { $policy->addAllowedFontDomain($fontOrigin); }
        $r->setContentSecurityPolicy($policy);$r->addHeader('Cache-Control','no-store, max-age=0');$r->addHeader('Referrer-Policy','no-referrer');
        $r->addHeader('X-Robots-Tag',$params['seo']['robots']);
        return $r;
    }

    private function seo(string $title,string $description,string $url,string $image,string $site,bool $embed,int $status): array {
        return ['title'=>$title,'description'=>mb_substr(trim(preg_replace('/\s+/u',' ',strip_tags($description))),0,300),
            'url'=>$status===200?$url:'','image'=>$status===200?$image:'','site'=>$site,
            'robots'=>$status!==200?'noindex, nofollow':($embed?'noindex, follow':'index, follow')];
    }
    private function nativeHead(array $seo): void {
        foreach (\OCA\PhotoGallery\Service\Seo::tags($seo) as [$tag,$attributes]) {
            \OCP\Util::addHeader($tag,$attributes);
        }
    }

}
