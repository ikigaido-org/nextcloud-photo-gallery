<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\ISession;
use OCP\IUserSession;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
final class AccountSwitch {
    public const KEY = 'photo_gallery.account_switch';
    public function __construct(private Settings $settings, private ToolAccess $access, private SwitchTarget $targets,
        private ISession $session, private IUserSession $userSession, private IUserManager $users, private LoggerInterface $logger, private \OCP\IConfig $config) {}

    /** Run while authentication apps boot, before Files/Photos initialize their user context. */
    public function prepare(): void {
        $state = $this->session->get(self::KEY);
        if ($state === null) { return; }
        try {
            if (!is_array($state) || !is_string($state['actor'] ?? null) || !is_string($state['target'] ?? null)) { throw new \RuntimeException('Invalid switch state'); }
            if (($state['generation'] ?? '') !== $this->config->getAppValue('photo_gallery','switchGeneration','0')) { throw new \RuntimeException('Switch generation expired'); }
            $current = $this->userSession->getUser(); // Validates the original browser session token.
            if ($current === null) { $this->session->remove(self::KEY); return; }
            if ($this->session->get('user_id') !== $state['actor'] || $this->userSession->getImpersonatingUserID() !== $state['actor']
                || !in_array($current->getUID(), [$state['actor'], $state['target']], true)) { throw new \RuntimeException('Session identity changed'); }
            $actor = $this->users->get($state['actor']);
            if ($actor === null || !$this->access->allowedForUser($actor) || $this->settings->get()['switchTarget'] !== $state['target']) {
                throw new \RuntimeException('Delegation withdrawn');
            }
            $target = $this->targets->validate($state['target']);
            // Keep the authenticated account in persistent session storage. Only this
            // request acts as the target, so disabling this app cannot leave a target login behind.
            $this->userSession->setVolatileActiveUser($target);
        } catch (\Throwable $e) {
            $this->logger->warning('Gallery account switch ended: session or authorization no longer valid', ['app'=>'photo_gallery','reason'=>$e->getMessage()]);
            // Do not execute an in-flight target operation as the original account.
            $this->userSession->logout();
        }
    }
    public function state(): ?array {
        $current = $this->userSession->getUser();
        if ($current === null) { return null; }
        $state = $this->session->get(self::KEY);
        if (is_array($state) && $this->session->get('user_id') === ($state['actor'] ?? null)
            && $this->userSession->getImpersonatingUserID() === ($state['actor'] ?? null) && $current->getUID() === ($state['target'] ?? null)) {
            return ['active'=>true, 'actor'=>$state['actor'], 'actorLabel'=>$this->users->get($state['actor'])?->getDisplayName() ?? $state['actor'], 'target'=>$state['target'], 'label'=>$current->getDisplayName()];
        }
        if ($state !== null || $this->userSession->getImpersonatingUserID() !== null || !$this->access->allowedForUser($current)) { return null; }
        $uid = $this->settings->get()['switchTarget'];
        if ($uid === '' || $uid === $current->getUID()) { return null; }
        try { $target = $this->targets->validate($uid); } catch (\InvalidArgumentException) { return null; }
        return ['active'=>false, 'actor'=>$current->getUID(), 'target'=>$uid, 'label'=>$target->getDisplayName()];
    }
    public function start(string $expectedUser, string $expectedTarget): void {
        $state = $this->state();
        if ($state === null || $state['active']) { throw new \DomainException('Für dieses Konto ist kein Galeriekontowechsel verfügbar.'); }
        if ($state['actor'] !== $expectedUser || $state['target'] !== $expectedTarget) { throw new \DomainException('Die Sitzung oder das Galeriekonto wurde geändert. Bitte die Seite neu laden.'); }
        if ($this->session->get('user_id') !== $state['actor'] || $this->session->get('app_password') !== null) {
            throw new \DomainException('Der Kontowechsel erfordert eine angemeldete Browsersitzung.');
        }
        $target = $this->targets->validate($state['target']);
        $this->userSession->setImpersonatingUserID();
        $this->session->set(self::KEY, ['actor'=>$state['actor'],'target'=>$state['target'],'generation'=>$this->config->getAppValue('photo_gallery','switchGeneration','0')]);
        $this->session->remove('last-password-confirm');
        $this->userSession->setVolatileActiveUser($target);
        $this->logger->warning('Gallery account switch started', ['app'=>'photo_gallery','actor'=>$state['actor'],'target'=>$state['target']]);
    }
    public function finish(string $expectedTarget): void {
        $state = $this->state();
        if ($state === null || !$state['active'] || $state['target'] !== $expectedTarget) { throw new \DomainException('Kein passender Galeriekontowechsel aktiv. Bitte die Seite neu laden.'); }
        $actor = $this->users->get($state['actor']);
        if ($actor === null || !$actor->isEnabled()) { $this->userSession->logout(); throw new \DomainException('Das persönliche Konto ist nicht mehr verfügbar. Bitte erneut anmelden.'); }
        $this->userSession->setVolatileActiveUser($actor);
        $this->userSession->setImpersonatingUserID(false);
        $this->session->remove(self::KEY);
        $this->session->remove('last-password-confirm');
        $this->logger->warning('Gallery account switch ended', ['app'=>'photo_gallery','actor'=>$state['actor'],'target'=>$state['target']]);
    }
}
