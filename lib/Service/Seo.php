<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
final class Seo {
    public static function tags(array $s): array {
        $tags=[['meta',['name'=>'description','content'=>$s['description']]],['meta',['name'=>'robots','content'=>$s['robots']]]];
        foreach(($s['feeds']??[]) as $format=>$feed){$tags[]=['link',['rel'=>'alternate','type'=>$feed['type'],'title'=>strtoupper($format).' – '.$s['site'],'href'=>$feed['url']]];}
        if($s['url']===''){return $tags;}
        $tags[]=['link',['rel'=>'canonical','href'=>$s['url']]];
        foreach(['title'=>$s['title'],'description'=>$s['description'],'type'=>'website','url'=>$s['url'],'site_name'=>$s['site'],'locale'=>'de_CH'] as $key=>$value){$tags[]=['meta',['property'=>'og:'.$key,'content'=>$value]];}
        if($s['image']!==''){
            $tags[]=['meta',['property'=>'og:image','content'=>$s['image']]];
            $tags[]=['meta',['property'=>'og:image:alt','content'=>$s['title']]];
            $tags[]=['meta',['name'=>'twitter:card','content'=>'summary_large_image']];
        }
        return $tags;
    }
}
