<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\IURLGenerator;

/** Page URL generation only. Routing at the external hostname belongs to its proxy. */
final class PublicUrls {
    public function __construct(private IURLGenerator $urls) {}
    public static function validOrigin(string $url): bool {
        $p=parse_url($url);
        return $p!==false && filter_var($url,FILTER_VALIDATE_URL)!==false && ($p['scheme']??'')==='https'
            && !empty($p['host']) && !isset($p['user']) && !isset($p['pass'])
            && !isset($p['path']) && !isset($p['query']) && !isset($p['fragment']);
    }
    public static function slug(string $name): string {
        $name=strtr(mb_strtolower($name,'UTF-8'),['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','é'=>'e','è'=>'e','ê'=>'e','à'=>'a','á'=>'a','â'=>'a','ç'=>'c','ô'=>'o','ó'=>'o','í'=>'i','ï'=>'i','ú'=>'u']);
        $slug=trim(preg_replace('/[^a-z0-9]+/','-',$name),'-');
        $slug=trim(substr($slug,0,140),'-');
        if($slug==='' || in_array($slug,['apps','custom-apps','core','login','logout','index-php','remote-php','status-php','ocs','ocs-provider','s','f','a','api','media','settings','_gallery'],true)){$slug='album-'.$slug;}
        return rtrim($slug,'-');
    }
    public static function slugs(array $rows): array {
        $groups=[];foreach($rows as $row){$groups[self::slug($row['name'])][]=(int)$row['album_id'];}
        $out=[];$used=array_fill_keys(array_keys($groups),true);
        foreach($groups as $base=>$ids){
            sort($ids,SORT_NUMERIC);
            foreach($ids as $id){$slug=$base;
                if(count($ids)>1){$slug=$base.'-'.$id;while(isset($used[$slug])){$slug.='-'.$id;}}
                $used[$slug]=true;$out[$id]=$slug;
            }
        }
        return $out;
    }
    public function index(array $s, array $query=[]): string {
        if(!empty($s['cleanUrls']) && self::validOrigin($s['publicBaseUrl']??'')){
            return $s['publicBaseUrl'].'/'.($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
        }
        return $this->urls->linkToRoute('photo_gallery.page.index',$query);
    }
    public function album(array $s, int $id, string $slug, array $query=[]): string {
        if(!empty($s['cleanUrls']) && self::validOrigin($s['publicBaseUrl']??'')){
            return $s['publicBaseUrl'].'/'.rawurlencode($slug).($query?'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986):'');
        }
        return $this->urls->linkToRoute('photo_gallery.page.namedAlbum',['slug'=>$slug]+$query);
    }
    public function absolute(array $settings, string $url): string {
        if (str_starts_with($url, 'https://')) { return $url; }
        if (!empty($settings['cleanUrls']) && self::validOrigin($settings['publicBaseUrl'] ?? '')) {
            return $settings['publicBaseUrl'].'/'.ltrim($url, '/');
        }
        return $this->urls->getAbsoluteURL($url);
    }
    /** Origin-relative media URLs keep CSP and Range requests same-origin behind a proxy. */
    public function resource(string $route,array $params=[]): string {
        $url=$this->urls->linkToRoute($route,$params);$p=parse_url($url);
        return ($p['path']??$url).(isset($p['query'])?'?'.$p['query']:'');
    }
}
