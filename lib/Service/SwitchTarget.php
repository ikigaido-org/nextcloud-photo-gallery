<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\Group\ISubAdmin;
use OCP\App\IAppManager;
use OCP\Encryption\IManager;
final class SwitchTarget {
    public function __construct(private IUserManager $users, private IGroupManager $groups, private ISubAdmin $subAdmins, private IAppManager $apps, private IManager $encryption) {}
    public function validate(string $uid): IUser {
        $user = $this->users->get($uid);
        if ($user === null || !$user->isEnabled()) { throw new \InvalidArgumentException('Das Galeriekonto existiert nicht oder ist deaktiviert.'); }
        $uid = $user->getUID();
        if ($this->groups->isAdmin($uid) || $this->groups->isDelegatedAdmin($uid) || $this->subAdmins->isSubAdmin($user)) {
            throw new \InvalidArgumentException('Als Galeriekonto ein Konto ohne Administrationsrechte verwenden.');
        }
        if ($user->getLastLogin() === 0) { throw new \InvalidArgumentException('Bitte zuerst einmal direkt beim Galeriekonto anmelden.'); }
        if (!$this->apps->isEnabledForUser('photos', $user) || !$this->apps->isEnabledForUser('photo_gallery', $user)) {
            throw new \InvalidArgumentException('Photos und Photo Gallery müssen für das Galeriekonto aktiviert sein.');
        }
        if ($this->encryption->isEnabled()) { throw new \InvalidArgumentException('Der Kontowechsel unterstützt keine serverseitige Dateiverschlüsselung.'); }
        return $user;
    }
}
