<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\ICallbackResponse;
use OCP\AppFramework\Http\IOutput;
use OCP\Files\File;

/** Bounded streaming, including single HTTP ranges needed for seeking on iOS. */
final class MediaResponse extends Response implements ICallbackResponse {
    private $stream = null;
    private int $length=0;
    public static function range(string $range, int $size): ?array {
        if ($range==='') { return [0,max(0,$size-1)]; }
        if ($size<=0 || !preg_match('/^bytes=(\d*)-(\d*)$/D',$range,$m) || ($m[1]==='' && $m[2]==='')) { return null; }
        if ($m[1]==='') { $n=(int)$m[2]; if ($n<=0) { return null; } return [max(0,$size-$n),$size-1]; }
        $start=(int)$m[1]; $end=$m[2]===''?$size-1:min((int)$m[2],$size-1);
        return $start>$end||$start>=$size?null:[$start,$end];
    }
    public function __construct(File $file, string $range) {
        $size=(int)$file->getSize(); $bounds=self::range($range,$size);
        parent::__construct($bounds===null?416:($range===''?200:206));
        $this->addHeader('Cache-Control','no-store, max-age=0');
        $this->addHeader('X-Content-Type-Options','nosniff');
        $this->addHeader('Referrer-Policy','no-referrer');
        $this->addHeader('Accept-Ranges','bytes');
        $this->addHeader('Content-Type',$file->getMimeType());
        // Do not expose disabled filename metadata in response headers.
        $this->addHeader('Content-Disposition','inline');
        if ($bounds===null) { $this->addHeader('Content-Range','bytes */'.$size); $this->addHeader('Content-Length','0'); return; }
        [$start,$end]=$bounds; $this->length=$size===0?0:$end-$start+1;
        $this->stream=$file->fopen('r');
        if (!is_resource($this->stream)) { throw new \RuntimeException('Media unavailable'); }
        if ($start>0 && fseek($this->stream,$start)!==0) { fclose($this->stream);$this->stream=null;throw new \RuntimeException('Storage does not support seeking'); }
        if ($range!=='') { $this->addHeader('Content-Range',"bytes $start-$end/$size"); }
        $this->addHeader('Content-Length',(string)$this->length);
    }
    public function callback(IOutput $output) {
        if (!is_resource($this->stream)) { return; }
        try {
            $remaining=$this->length;
            while ($remaining>0 && !feof($this->stream) && !connection_aborted()) {
                $chunk=fread($this->stream,min(1048576,$remaining));
                if ($chunk===false||$chunk==='') { break; }
                $output->setOutput($chunk); $remaining-=strlen($chunk);
            }
        } finally { fclose($this->stream);$this->stream=null; }
    }
    public function __destruct() { if (is_resource($this->stream)) { fclose($this->stream); } }
}
