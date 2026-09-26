<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Listener;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\INavigationManager;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use OCA\PhotoGallery\Service\SwitchUi;
/** @implements IEventListener<LoadAdditionalEntriesEvent> */
final class AccountMenuListener implements IEventListener {
    public function __construct(private SwitchUi $ui, private INavigationManager $navigation) {}
    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalEntriesEvent) { return; }
        $entry = $this->ui->menuEntry();
        if ($entry !== null) { $this->navigation->add($entry); }
    }
}
