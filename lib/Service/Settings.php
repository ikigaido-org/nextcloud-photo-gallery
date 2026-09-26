<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;

use OCA\PhotoGallery\AppInfo\Application;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;

final class Settings {
    public const TITLE = 'Fotogalerie';
    public const DESCRIPTION = 'Fotos und Videos unserer Veranstaltungen';
    public function __construct(private IConfig $config, private IUserManager $users, private Appearance $appearance, private IGroupManager $groups, private SwitchTarget $switchTargets) {}

    public function get(): array {
        $value = $this->config->getAppValue(Application::APP_ID, 'settings', '');
        $saved = json_decode($value, true);
        if (!is_array($saved)) {
            $saved = [];
        }
        // Defaults below fill missing keys, including on older settings schemas.
        // Saved texts and choices always take precedence. No writes on public reads.
        foreach(['yearColor','navigationColor','footerColor'] as $color){
            $saved['appearance'][$color] ??= $saved['appearance']['textColor'] ?? Appearance::COLORS[$color];
        }
        unset($saved['origin'],$saved['covers']);
        $saved['appearance'] = array_replace(Appearance::defaults(), $saved['appearance'] ?? []);
        return array_replace([
            'schema' => 2, 'coverMatch'=>'cover_', 'cleanUrls'=>false, 'publicBaseUrl'=>'',
            'enabled' => false,
            'switchTarget' => '', 'restrictTools' => false, 'toolUsers' => [], 'toolGroups' => [],
            'feedScope' => 'current', 'feedFromYear' => (int)gmdate('Y'),
            'title' => self::TITLE,
            'description' => self::DESCRIPTION,
            'owners' => [],
            'sort' => 'folder_date',
            'yearView' => 'latest',
            'dateOptions' => FolderDate::defaults(), 'showHeader' => false, 'footerHtml' => '',
            'appearance' => Appearance::defaults(), 'metadataFields' => Metadata::publicDefaults(),
        ], $saved);
    }

    public function save(bool $enabled, string $title, string $description, string $owners, string $sort,
        ?array $appearance = null, ?string $yearView = null,
        ?array $dateOptions = null, ?bool $showHeader = null, ?string $footerHtml = null, ?array $metadataFields = null, ?string $coverMatch=null, ?bool $cleanUrls=null, ?string $publicBaseUrl=null, ?bool $restrictTools=null, ?string $toolUsers=null, ?string $toolGroups=null, ?string $feedScope=null, ?int $feedFromYear=null, ?string $switchTarget=null): void {
        $previous = $this->get();
        $switchTarget = trim($switchTarget ?? $previous['switchTarget']);
        if ($switchTarget !== '') { $switchTarget = $this->switchTargets->validate($switchTarget)->getUID(); }
        $restrictTools ??= $previous['restrictTools'];
        $toolUserIds = $this->validateIds($toolUsers ?? implode("\n", $previous['toolUsers']), false);
        $toolGroupIds = $this->validateIds($toolGroups ?? implode("\n", $previous['toolGroups']), true);
        if ($restrictTools && !$toolUserIds && !$toolGroupIds) {
            throw new \InvalidArgumentException('Mindestens ein Konto oder eine Gruppe für die Albumwerkzeuge auswählen.');
        }
        $feedScope ??= $previous['feedScope'];
        $feedFromYear ??= $previous['feedFromYear'];
        if (!in_array($feedScope, ['current','from','all'], true) || $feedFromYear < 1900 || $feedFromYear > 2099) {
            throw new \InvalidArgumentException('Ungültige Feed-Auswahl oder Jahreszahl (1900–2099).');
        }
        $coverMatch=trim($coverMatch ?? $previous['coverMatch']);
        if(mb_strlen($coverMatch)>80 || preg_match('/[\x00-\x1f\x7f]/',$coverMatch)){throw new \InvalidArgumentException('Titelbild-Suchtext: höchstens 80 Zeichen, keine Steuerzeichen.');}
        $cleanUrls ??= $previous['cleanUrls'];
        $publicBaseUrl=rtrim(trim($publicBaseUrl ?? $previous['publicBaseUrl']),'/');
        if($publicBaseUrl!=='' && !PublicUrls::validOrigin($publicBaseUrl)){throw new \InvalidArgumentException('Öffentliche Basisadresse: HTTPS ohne Pfad, Benutzerinfo oder Parameter.');}
        if($cleanUrls && $publicBaseUrl===''){throw new \InvalidArgumentException('Für kurze URLs eine öffentliche Basisadresse angeben.');}
        $metadataFields = Metadata::validate($metadataFields ?? $previous['metadataFields']);
        $dateOptions = FolderDate::validate(array_replace($previous['dateOptions'], $dateOptions ?? []));
        $showHeader ??= $previous['showHeader'];
        $footerHtml = Footer::clean($footerHtml ?? $previous['footerHtml']);
        $appearance = $this->appearance->validate(array_replace($previous['appearance'], $appearance ?? []));
        $yearView ??= $previous['yearView'];
        if (!in_array($yearView, ['latest', 'all', 'off'], true)) {
            throw new \InvalidArgumentException('Ungültige Jahresansicht.');
        }
        $title = trim($title);
        $description = trim($description);
        if ($title === '' || mb_strlen($title) > 120 || mb_strlen($description) > 1000) {
            throw new \InvalidArgumentException('Titel: 1–120 Zeichen; Beschreibung: höchstens 1000 Zeichen.');
        }
        if (!in_array($sort, ['folder_date', 'name', 'newest'], true)) {
            throw new \InvalidArgumentException('Ungültige Sortierung.');
        }
        if (strlen($owners) > 16384) {
            throw new \InvalidArgumentException('Die Kontenliste ist zu lang.');
        }
        $ids = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', $owners) ?: []),
            static fn (string $id): bool => $id !== '')));
        if (count($ids) > 100) {
            throw new \InvalidArgumentException('Höchstens 100 Konten sind möglich.');
        }
        foreach ($ids as $id) {
            if ($this->users->get($id) === null) {
                throw new \InvalidArgumentException('Unbekannte Konto-ID: ' . $id);
            }
        }
        // A single config write keeps publication and scope consistent.
        $this->config->setAppValue(Application::APP_ID, 'settings', json_encode([
            'schema' => 2, 'coverMatch'=>$coverMatch, 'cleanUrls'=>$cleanUrls, 'publicBaseUrl'=>$publicBaseUrl, 'metadataFields' => $metadataFields,
            'switchTarget'=>$switchTarget, 'restrictTools'=>$restrictTools, 'toolUsers'=>$toolUserIds, 'toolGroups'=>$toolGroupIds,
            'feedScope'=>$feedScope, 'feedFromYear'=>$feedFromYear,
            'enabled' => $enabled, 'title' => $title, 'description' => $description,
            'owners' => $ids, 'sort' => $sort,
            'appearance' => $appearance, 'yearView' => $yearView,
            'dateOptions' => $dateOptions, 'showHeader' => $showHeader, 'footerHtml' => $footerHtml,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
    private function validateIds(string $value, bool $groups): array {
        if (strlen($value) > 16384) { throw new \InvalidArgumentException('Die Zugriffsliste ist zu lang.'); }
        $ids = array_values(array_unique(array_filter(array_map('trim', preg_split('/\R/u', $value) ?: []), static fn($v) => $v !== '')));
        if (count($ids) > 100) { throw new \InvalidArgumentException('Höchstens 100 Einträge pro Zugriffsliste.'); }
        foreach ($ids as $id) {
            if (($groups ? $this->groups->get($id) : $this->users->get($id)) === null) {
                throw new \InvalidArgumentException(($groups ? 'Unbekannte Gruppen-ID: ' : 'Unbekannte Konto-ID: ') . $id);
            }
        }
        return $ids;
    }

}
