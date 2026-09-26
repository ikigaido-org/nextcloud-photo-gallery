<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\App\IAppManager;
final class ToolAccess {
    public function __construct(private Settings $settings, private IUserSession $session, private IGroupManager $groups, private IAppManager $apps, private \OCP\ISession $storage, private \OCP\IUserManager $users) {}
    public function allowed(): bool {
        $user = $this->session->getUser();
        if ($user === null || !$user->isEnabled() || !$this->apps->isEnabledForUser('photo_gallery', $user) || !$this->apps->isEnabledForUser('photos', $user)) { return false; }
        // The folder action belongs to the configured working account. The
        // personal-account allowlist below still controls who may switch into it.
        $target = $this->settings->get()['switchTarget'];
        if ($target !== '' && $user->getUID() !== $target) { return false; }
        $state = $this->storage->get(AccountSwitch::KEY);
        if (is_array($state) && ($state['target'] ?? null) === $user->getUID()
            && ($state['actor'] ?? null) === $this->storage->get('user_id')
            && ($state['actor'] ?? null) === $this->session->getImpersonatingUserID()) {
            $actor = $this->users->get($state['actor']);
            return $actor !== null && $this->allowedForUser($actor);
        }
        // A direct login as the working account also has the folder action.
        if ($target !== '') { return $state === null; }
        return $this->allowedForUser($user);
    }
    public function allowedForUser($user): bool {
        if (!$user->isEnabled() || !$this->apps->isEnabledForUser('photo_gallery', $user) || !$this->apps->isEnabledForUser('photos', $user)) { return false; }
        $s = $this->settings->get();
        if (!$s['restrictTools']) { return true; }
        if (in_array($user->getUID(), $s['toolUsers'], true)) { return true; }
        foreach ($s['toolGroups'] as $gid) {
            if ($this->groups->isInGroup($user->getUID(), $gid)) { return true; }
        }
        return false;
    }
}
