<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\IConfig;
use OCP\IURLGenerator;
final class Feed {
    public function __construct(private Catalog $catalog,private PublicUrls $urls,private IURLGenerator $generator,private IConfig $config) {}
    public function links(array $s): array {
        $out=[];foreach(['rss'=>'application/rss+xml','atom'=>'application/atom+xml'] as $format=>$type){
            $out[$format]=['type'=>$type,'url'=>$this->urls->absolute($s,$this->urls->resource('photo_gallery.feed.'.$format))];
        }return $out;
    }
    private static function x(string $s): string {
        $s=preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u','',$s)??'';
        return htmlspecialchars($s,ENT_XML1|ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }
    public function render(array $s,string $format): string {
        if(!$s['enabled']){throw new \InvalidArgumentException('Gallery unpublished');}
        // Feed scope uses event years, independent of the index browser filter.
        $s['sort']='folder_date';
        $scope=$s['feedScope']??'current';
        $page=$this->catalog->getPage($s,1,$scope==='current'?gmdate('Y'):'all',true);
        $links=$this->links($s);$home=$this->urls->absolute($s,$this->urls->index($s));
        // Stable IDs survive title, slug and public hostname changes.
        $namespace=hash('sha256',$this->config->getSystemValue('instanceid',$this->generator->getAbsoluteURL('/')));
        $id='urn:photo-gallery:'.$namespace;
        $old=json_decode($this->config->getAppValue('photo_gallery','feedState','{}'),true);if(!is_array($old)){$old=[];}
        $state=[];$items=[];$now=time();
        foreach($page['cards'] as $card){
            if($scope==='from' && ($card['eventYear']===null || (int)$card['eventYear']<(int)$s['feedFromYear'])){continue;}
            $sharing=$this->catalog->sharing($s,$card['id']);if(!$sharing){continue;}
            $title=$card['name'].($card['location']!==''?' ('.$card['location'].')':'');
            $date=$card['eventDate']?(new \DateTimeImmutable($card['eventDate']))->format('d.m.Y'):($card['eventYear']??'');
            $caption=implode(' · ',array_filter([$card['location'],$date,$card['count'].' '.($card['count']===1?'Datei':'Dateien')],static fn($v)=>$v!==''));
            $html=($sharing['image']!==''?'<p><a href="'.self::x($sharing['url']).'"><img src="'.self::x($sharing['image']).'" alt="'.self::x($card['name']).'" width="640"></a></p>':'')
                .'<p>'.self::x($caption).'</p><p><a href="'.self::x($sharing['url']).'">Album ansehen</a></p>';
            $hash=hash('sha256',$title.$html);$prior=$old[$card['id']]??[];
            $entry=['hash'=>$hash,'published'=>(int)($prior['published']??$now),'updated'=>($prior['hash']??'')===$hash?(int)$prior['updated']:$now];
            $state[$card['id']]=$entry;$items[]=['id'=>$id.':album:'.$card['id'],'title'=>$title,'html'=>$html,'url'=>$sharing['url']]+$entry;
        }
        $updated=max(array_merge([0],array_column($state,'updated')));
        $feedHash=hash('sha256',json_encode([$s['title'],$s['description'],$home,$state]));
        $state['_feed']=['hash'=>$feedHash,'updated'=>($old['_feed']['hash']??'')===$feedHash?(int)$old['_feed']['updated']:max($now,$updated)];
        if($state!==$old){$this->config->setAppValue('photo_gallery','feedState',json_encode($state,JSON_THROW_ON_ERROR));}
        $x=fn($v)=>self::x((string)$v);$xml='<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        if($format==='rss'){
            $xml.='<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>'.$x($s['title']).'</title><link>'.$x($home).'</link><description>'.$x($s['description']?:'Neue Foto- und Videoalben').'</description><language>de-CH</language><atom:link href="'.$x($links['rss']['url']).'" rel="self" type="application/rss+xml"/><lastBuildDate>'.gmdate(DATE_RSS,$state['_feed']['updated']).'</lastBuildDate>';
            foreach($items as $item){$xml.='<item><title>'.$x($item['title']).'</title><link>'.$x($item['url']).'</link><guid isPermaLink="false">'.$x($item['id']).'</guid><pubDate>'.gmdate(DATE_RSS,$item['published']).'</pubDate><description>'.$x($item['html']).'</description></item>';}
            return $xml.'</channel></rss>';
        }
        $xml.='<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="de-CH"><id>'.$x($id).'</id><title>'.$x($s['title']).'</title><subtitle>'.$x($s['description']).'</subtitle><author><name>'.$x($s['title']).'</name></author><updated>'.gmdate(DATE_ATOM,$state['_feed']['updated']).'</updated><link rel="self" type="application/atom+xml" href="'.$x($links['atom']['url']).'"/><link rel="alternate" type="text/html" href="'.$x($home).'"/>';
        foreach($items as $item){$xml.='<entry><id>'.$x($item['id']).'</id><title>'.$x($item['title']).'</title><link rel="alternate" type="text/html" href="'.$x($item['url']).'"/><published>'.gmdate(DATE_ATOM,$item['published']).'</published><updated>'.gmdate(DATE_ATOM,$item['updated']).'</updated><content type="html">'.$x($item['html']).'</content></entry>';}
        return $xml.'</feed>';
    }
}
