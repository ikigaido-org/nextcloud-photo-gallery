<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\ICallbackResponse;
use OCP\AppFramework\Http\IOutput;
final class FeedResponse extends Response implements ICallbackResponse {
    public function __construct(private string $body,string $type,int $status=200){
        parent::__construct($status);$this->addHeader('Content-Type',$type.'; charset=utf-8');
        $this->addHeader('Cache-Control','no-store, max-age=0');$this->addHeader('X-Content-Type-Options','nosniff');
        $this->addHeader('X-Robots-Tag','noindex, follow');
    }
    public function callback(IOutput $output){$output->setOutput($this->body);}
}
