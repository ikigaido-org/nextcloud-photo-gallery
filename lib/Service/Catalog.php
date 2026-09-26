<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;

use OCA\PhotoGallery\Db\AlbumRepository;
use OCP\App\IAppManager;
use OCP\IUserManager;
use OCP\IURLGenerator;

final class Catalog {
    public const PAGE_SIZE = 24;
    public function __construct(
        private AlbumRepository $albums,
        private IAppManager $apps,
        private IUserManager $users,
        private IURLGenerator $urls,
        private PublicUrls $publicUrls,
    ) {}

    private function publicRows(array $settings): array {
        if (!$this->apps->isEnabledForAnyone('photos')) {
            throw new \RuntimeException('Photos muss aktiviert sein.');
        }
        $rows = $this->albums->findPublic($settings['owners']);
        $eligible = [];
        $ownerStatus = [];
        foreach ($rows as $row) {
            $uid = $row['user'];
            if (!array_key_exists($uid, $ownerStatus)) {
                $user = $this->users->get($uid);
                $ownerStatus[$uid] = $user !== null && $user->isEnabled()
                    && $this->apps->isEnabledForUser('photos', $user);
            }
            if (!$ownerStatus[$uid] || !is_string($row['token']) || $row['token'] === '') {
                continue;
            }
            // Photos has one public link per album. De-duplicate defensively.
            $eligible[(int)$row['album_id']] = $row;
        }
        return array_values($eligible);
    }

    public function getPage(array $settings, int $page, string $year = 'all', bool $groupYears = false, string $location = '', bool $embed = false): array {
        if (!in_array($year, ['all', 'latest', 'undated'], true) && !preg_match('/^(19|20)\d{2}$/D', $year)) {
            throw new \InvalidArgumentException('Ungültige Jahresauswahl.');
        }
        if(mb_strlen($location)>200){throw new \InvalidArgumentException('Ungültiger Ort.');}
        $rows = $this->publicRows($settings);
        $slugs=PublicUrls::slugs($rows);
        $locations=array_values(array_unique(array_filter(array_map(static fn($r)=>trim((string)($r['location']??'')),$rows),static fn($v)=>$v!=='')));
        usort($locations,'strnatcasecmp');
        $options = $settings['dateOptions'] ?? FolderDate::defaults();
        $sources = $options['source'] === 'album' ? $rows : $this->albums->getSourceFolders(array_column($rows, 'album_id'));
        $assignments = FolderDate::forAlbums($sources, $options);
        $dates = []; $eventYears = [];
        foreach ($assignments as $id => $value) { $dates[$id] = $value['date']; $eventYears[$id] = $value['year']; }
        $years = [];
        foreach ($rows as $row) {
            $key = $eventYears[(int)$row['album_id']] ?? 'undated';
            $years[$key] = ($years[$key] ?? 0) + 1;
        }
        uksort($years, static fn ($a, $b): int => strcmp($b === 'undated' ? '' : (string)$b, $a === 'undated' ? '' : (string)$a));
        $allTotal = count($rows);
        if ($year === 'latest') { $year = (string)(array_key_first($years) ?? 'all'); }
        if ($year !== 'all') {
            $rows = array_values(array_filter($rows, static function ($row) use ($eventYears, $year): bool {
                return ($eventYears[(int)$row['album_id']] ?? 'undated') === $year;
            }));
        }
        if($location!==''){$rows=array_values(array_filter($rows,static fn($r)=>trim((string)($r['location']??''))===$location));}
        usort($rows, static function (array $a, array $b) use ($settings, $dates, $eventYears, $groupYears): int {
            if ($groupYears) {
                $aYear = $eventYears[(int)$a['album_id']] ?? '';
                $bYear = $eventYears[(int)$b['album_id']] ?? '';
                $group = strcmp($bYear, $aYear);
                if ($group !== 0) { return $group; }
            }
            if ($settings['sort'] === 'folder_date') {
                $date = strcmp($dates[(int)$b['album_id']] ?? '', $dates[(int)$a['album_id']] ?? '');
                if ($date !== 0) { return $date; }
            }
            if ($settings['sort'] === 'newest') {
                $time = (int)$b['created'] <=> (int)$a['created'];
                if ($time !== 0) { return $time; }
            }
            return strnatcasecmp($a['name'], $b['name']) ?: ((int)$a['album_id'] <=> (int)$b['album_id']);
        });
        $total = count($rows);
        $pages = max(1, (int)ceil($total / self::PAGE_SIZE));
        $page = max(1, min($page, $pages));
        $rows = array_slice($rows, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE);
        $summary = $this->albums->getMediaSummary(array_map(static fn ($r) => (int)$r['album_id'], $rows));
        $covers = $this->albums->getCovers(array_column($rows, 'album_id'));
        $covers=array_replace($covers,$this->albums->getFilenameCovers(array_column($rows,'album_id'),$settings['coverMatch']??''));
        $types=$this->albums->getMediaTypes(array_merge(array_values($covers),array_column($summary,'preview')));
        $cards = [];
        foreach ($rows as $row) {
            $media = $summary[(int)$row['album_id']] ?? ['count' => 0, 'preview' => 0];
            $media['preview'] = $covers[(int)$row['album_id']] ?? $media['preview'];
            $cards[] = [
                'id' => (int)$row['album_id'],
                'name' => $row['name'],
                'coverId'=>(int)$media['preview'],
                'location' => trim((string)($row['location'] ?? '')),
                'eventDate' => $dates[(int)$row['album_id']] ?? null,
                'eventYear' => $eventYears[(int)$row['album_id']] ?? null,
                'count' => $media['count'],
                'isVideo' => str_starts_with($types[$media['preview']] ?? '', 'video/'),
                'videoStream' => str_starts_with($types[$media['preview']] ?? '', 'video/') ? $this->publicUrls->resource('photo_gallery.media.stream', ['id'=>(int)$row['album_id'],'fileId'=>$media['preview']]) : null,
                'url' => $this->publicUrls->album($settings,(int)$row['album_id'],$slugs[(int)$row['album_id']],['year'=>$year,'page'=>$page,'location'=>$location]+($embed?['embed'=>1]:[])),
                'preview' => $media['preview'] > 0 ? $this->publicUrls->resource('photo_gallery.media.preview', ['id' => (int)$row['album_id'], 'fileId' => $media['preview']]) : null,
            ];
        }
        // Owner IDs are deliberately not sent to the public template.
        $groups = [];
        foreach ($cards as $card) {
            $key = $groupYears ? ($card['eventYear'] ?? 'undated') : 'all';
            if (!isset($groups[$key])) {
                $groups[$key] = ['year' => (string)$key,
                    'label' => $key === 'undated' ? 'Ohne Jahreszuordnung' : (string)$key, 'cards' => []];
            }
            $groups[$key]['cards'][] = $card;
        }
        $availableYears = [];
        foreach ($years as $key => $count) {
            $availableYears[] = ['value' => (string)$key, 'label' => $key === 'undated' ? 'Ohne Jahreszuordnung' : (string)$key, 'count' => $count];
        }
        return ['cards' => $cards, 'total' => $total, 'allTotal' => $allTotal, 'page' => $page,
            'pages' => $pages, 'year' => $year, 'locations'=>$locations, 'selectedLocation'=>$location, 'years' => $availableYears, 'groups' => array_values($groups)];
    }

    /** No token or owner identifiers leave this service. Recheck publication on every access. */
    public function resolve(array $settings, int $id): ?array {
        if (!$settings['enabled']) { return null; }
        foreach ($this->publicRows($settings) as $row) {
            if ((int)$row['album_id'] === $id) { return $row; }
        }
        return null;
    }

    public function resolveSlug(array $settings,string $slug): ?array {
        if(!$settings['enabled']){return null;}
        $rows=$this->publicRows($settings);$slugs=PublicUrls::slugs($rows);
        foreach($rows as $row){if($slugs[(int)$row['album_id']]===$slug){return $row;}}
        return null;
    }

    /** Reuses the same filename > Photos > fallback cover precedence as the index. */
    public function sharing(array $settings, int $id): array {
        $album=$this->resolve($settings,$id);
        if (!$album) { return []; }
        $slugs=PublicUrls::slugs($this->publicRows($settings));
        $summary=$this->albums->getMediaSummary([$id]);
        $covers=array_replace($this->albums->getCovers([$id]),$this->albums->getFilenameCovers([$id],$settings['coverMatch']??''));
        $cover=(int)($covers[$id]??$summary[$id]['preview']??0);
        return ['url'=>$this->publicUrls->absolute($settings,$this->publicUrls->album($settings,$id,$slugs[$id])),
            'image'=>$cover>0?$this->publicUrls->absolute($settings,$this->publicUrls->resource('photo_gallery.media.preview',['id'=>$id,'fileId'=>$cover,'large'=>1])):''];
    }

    public function eventAssignment(array $settings, array $album): array {
        $options = $settings['dateOptions'] ?? FolderDate::defaults();
        $sources = $options['source'] === 'album' ? [$album] : $this->albums->getSourceFolders([(int)$album['album_id']]);
        return FolderDate::forAlbums($sources, $options)[(int)$album['album_id']] ?? ['date'=>null, 'year'=>null];
    }

    public function getAlbum(array $settings, int $id, int $after = 0): ?array {
        $album = $this->resolve($settings, $id);
        if ($album === null) { return null; }
        $rows = $this->albums->getPhotos($id, max(0, $after), 49);
        $hasMore = count($rows) > 48;
        $rows = array_slice($rows, 0, 48);
        $images = [];
        foreach ($rows as $row) {
            $params = ['id' => $id, 'fileId' => (int)$row['fileid']];
            $images[] = [
                'id' => (int)$row['fileid'],
                'kind' => str_starts_with($row['mimetype'], 'video/') ? 'video' : 'image',
                'mime' => $row['mimetype'],
                'thumbnail' => $this->publicUrls->resource('photo_gallery.media.preview', $params + ['fit'=>1]),
                'large' => $this->publicUrls->resource('photo_gallery.media.preview', $params + ['large' => 1]),
                'stream' => $this->publicUrls->resource('photo_gallery.media.stream', $params),
                'metadata' => $this->publicUrls->resource('photo_gallery.media.metadata', $params),
            ];
        }
        return ['id' => $id, 'name' => $album['name'], 'location' => trim((string)($album['location'] ?? '')),
            'images' => $images, 'hasMore' => $hasMore,
            'nextCursor' => $hasMore ? (int)$rows[count($rows)-1]['album_file_id'] : null];
    }
}
