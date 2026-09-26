<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\Files\File;
use OCP\IUserManager;

final class Metadata {
    public const LABELS = ['filename'=>'Dateiname','size'=>'Dateigrösse','date'=>'Aufnahmedatum und Uhrzeit',
        'dimensions'=>'Bildabmessungen und Megapixel','camera'=>'Kamera','lens'=>'Objektiv',
        'aperture'=>'Blende','exposure'=>'Belichtungszeit','focal'=>'Brennweite','iso'=>'ISO',
        'gps'=>'GPS-Position','owner'=>'Geteilt von'];
    public function __construct(private IUserManager $users) {}
    public static function defaults(): array { return array_fill_keys(array_keys(self::LABELS), true) + []; }
    public static function publicDefaults(): array { return array_replace(self::defaults(), ['gps'=>false,'owner'=>false]); }
    public static function validate(array $values): array {
        $out=[];
        foreach (self::LABELS as $key=>$label) {
            $v=$values[$key] ?? false;
            if (!in_array($v,[true,false,0,1,'0','1'],true)) { throw new \InvalidArgumentException('Ungültige Metadatenauswahl.'); }
            $out[$key]=in_array($v,[true,1,'1'],true);
        }
        return $out;
    }
    private static function number(mixed $v): ?float {
        if (is_numeric($v)) { return (float)$v; }
        if (is_string($v) && preg_match('~^(-?\d+(?:\.\d+)?)/(\d+(?:\.\d+)?)$~D',$v,$m) && (float)$m[2] !== 0.0) { return (float)$m[1]/(float)$m[2]; }
        return null;
    }
    private static function text(mixed $v): string {
        if (!is_scalar($v)) { return ''; }
        $s=(string)$v;
        if (!mb_check_encoding($s,'UTF-8')) { $s=mb_convert_encoding($s,'UTF-8','ISO-8859-1'); }
        return mb_substr(trim(str_replace("\0",'', $s)),0,300);
    }
    /** Format EXIF data without guessing timezone from server settings. */
    public static function formatExif(array $e): array {
        $out=[];
        $date=self::text($e['DateTimeOriginal'] ?? '');
        if (preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}:\d{2}:\d{2})$/D',$date,$m)) {
            if (checkdate((int)$m[2],(int)$m[3],(int)$m[1])) {
                $offset=self::text($e['OffsetTimeOriginal'] ?? '');
                $out['date']="$m[3].$m[2].$m[1] $m[4]" . (preg_match('/^[+-]\d{2}:\d{2}$/D',$offset) ? " $offset" : ' (Zeitzone unbekannt)');
            }
        }
        $out['camera']=trim(self::text($e['Make']??'').' '.self::text($e['Model']??''));
        $out['lens']=self::text($e['LensModel']??'');
        foreach (['FNumber'=>'aperture','FocalLength'=>'focal','ISOSpeedRatings'=>'iso'] as $key=>$field) {
            $n=self::number($e[$key]??null);
            if ($n !== null && $n>0) { $out[$field]=($field==='aperture'?'ƒ/':'').rtrim(rtrim(number_format($n,2,'.',''),'0'),'.').($field==='focal'?' mm':''); }
        }
        $n=self::number($e['ExposureTime']??null);
        if ($n !== null && $n>0) { $out['exposure']=($n<1?'1/'.round(1/$n):(string)round($n,3)).' s'; }
        $w=(int)($e['ExifImageWidth']??$e['COMPUTED']['Width']??0); $h=(int)($e['ExifImageLength']??$e['COMPUTED']['Height']??0);
        if ($w>0 && $h>0) { $out['dimensions']="$w × $h · ".number_format($w*$h/1000000,1,'.','').' MP'; }
        $coordinates=[];
        foreach (['Latitude','Longitude'] as $axis) {
            $v=$e['GPS'.$axis]??null; $ref=$e['GPS'.$axis.'Ref']??'';
            if (!is_array($v)||count($v)!==3) { continue; }
            $parts=array_map([self::class,'number'],array_values($v));
            if (in_array(null,$parts,true)||!in_array($ref,['N','S','E','W'],true)) { continue; }
            $n=($parts[0]+$parts[1]/60+$parts[2]/3600)*(in_array($ref,['S','W'],true)?-1:1);
            if (abs($n)<=($axis==='Latitude'?90:180)) { $coordinates[] = number_format($n,6,'.',''); }
        }
        if (count($coordinates)===2) { $out['gps']=implode(', ',$coordinates); }
        return array_filter($out,static fn($v)=>$v!=='');
    }
    public function read(File $file, array $allowed): array {
        $values=[];
        if ($allowed['filename']??false) { $values['filename']=self::text($file->getName()); }
        if ($allowed['size']??false) { $n=(float)$file->getSize(); $values['size']=$n>=1048576?number_format($n/1048576,2,',','').' MB':number_format($n/1024,1,',','').' kB'; }
        if ($allowed['owner']??false) { $owner=$file->getOwner(); if ($owner) { $values['owner']=self::text($owner->getDisplayName()); } }
        // Bound memory/network work: no video download and at most 16 MiB of image data.
        $need=array_filter(array_intersect_key($allowed,array_flip(['date','dimensions','camera','lens','aperture','exposure','focal','iso','gps'])));
        if ($need && in_array($file->getMimeType(),['image/jpeg','image/tiff','image/png','image/webp'],true)) {
            $source=$file->fopen('r');
            if (is_resource($source)) {
                try { $bytes=stream_get_contents($source,16*1024*1024); } finally { fclose($source); }
                if (is_string($bytes)) {
                    $e=[];
                    if (function_exists('exif_read_data') && in_array($file->getMimeType(),['image/jpeg','image/tiff'],true)) {
                        $temp=fopen('php://temp/maxmemory:2097152','w+b');
                        try { fwrite($temp,$bytes); rewind($temp); $e=@exif_read_data($temp) ?: []; } finally { fclose($temp); }
                    }
                    $values+=self::formatExif($e);
                    if (($allowed['dimensions']??false) && !isset($values['dimensions'])) {
                        $size=@getimagesizefromstring($bytes);
                        if ($size) { $values['dimensions']=$size[0].' × '.$size[1].' · '.number_format($size[0]*$size[1]/1000000,1,'.','').' MP'; }
                    }
                }
            }
        }
        $out=[];
        foreach (self::LABELS as $key=>$label) {
            if (($allowed[$key]??false) && isset($values[$key]) && $values[$key]!=='') { $out[]=['key'=>$key,'label'=>$label,'value'=>$values[$key]]; }
        }
        return $out;
    }
}
