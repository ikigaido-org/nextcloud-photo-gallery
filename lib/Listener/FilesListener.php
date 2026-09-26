<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Listener;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\PhotoGallery\Service\ToolAccess;
use OCP\Util;
/** @implements IEventListener<LoadAdditionalScriptsEvent> */
final class FilesListener implements IEventListener {
    public function __construct(private ToolAccess $access) {}
    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalScriptsEvent) { return; }
        if (!$this->access->allowed()) { return; }
        Util::addScript('photo_gallery','files-albums');
        Util::addStyle('photo_gallery','files-albums');
    }
}
