<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\IURLGenerator;
use OCP\Util;
use OCP\AppFramework\Services\IInitialState;
final class SwitchUi {
    private bool $suppressed = false;
    public function __construct(private AccountSwitch $switch, private IURLGenerator $urls, private IInitialState $initialState) {}
    public function data(): ?array {
        if ($this->suppressed) { return null; }
        $state = $this->switch->state();
        if ($state === null) { return null; }
        return $state + ['startUrl'=>$this->urls->linkToRoute('photo_gallery.switch.start'),
            'finishUrl'=>$this->urls->linkToRoute('photo_gallery.switch.finish'),
            'token'=>Util::callRegister(),
            'js'=>$this->urls->linkTo('photo_gallery','js/account-switcher.js').'?v=0.8.6',
            'css'=>$this->urls->linkTo('photo_gallery','css/account-switcher.css').'?v=0.8.6'];
    }
    /** Public gallery pages never receive account controls, even for editors. */
    public function suppress(): void {
        $this->suppressed = true;
        $this->initialState->provideInitialState('account-switcher', false);
    }
    public function menuEntry(): ?array {
        $state = $this->data();
        if ($state === null) { return null; }
        return ['id'=>'photo-gallery-account-switch', 'type'=>\OCP\INavigationManager::TYPE_SETTINGS,
            'order'=>99997, 'href'=>'',
            'name'=>$state['active'] ? 'Zurück zu '.$state['actorLabel'] : 'Als '.$state['label'].' arbeiten',
            'icon'=>$this->urls->linkTo('photo_gallery','img/account-switch.svg')];
    }
    public function load(): void {
        $state = $this->data();
        if ($state === null) { return; }
        $this->initialState->provideInitialState('account-switcher', $state);
        Util::addScript('photo_gallery','account-switcher');
        Util::addStyle('photo_gallery','account-switcher');
    }
}
