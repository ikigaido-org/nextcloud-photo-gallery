<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;

final class FolderDate {
    public const FORMATS = ['auto', 'yymmdd', 'yyyymmdd', 'ymd', 'dmy', 'year'];
    public static function defaults(): array {
        return ['source' => 'folders', 'format' => 'auto', 'position' => 'any', 'ancestors' => true];
    }
    public static function validate(array $input): array {
        $o = array_replace(self::defaults(), array_intersect_key($input, self::defaults()));
        if (!in_array($o['source'], ['folders', 'album'], true)
            || !in_array($o['format'], self::FORMATS, true)
            || !in_array($o['position'], ['start', 'any'], true)) {
            throw new \InvalidArgumentException('Ungültige Einstellung zur Datumszuordnung.');
        }
        $o['ancestors'] = filter_var($o['ancestors'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($o['ancestors'] === null) { throw new \InvalidArgumentException('Ungültige Ordnersuche.'); }
        return $o;
    }
    /** No artificial January 1 date for a year-only name. YY always means 2000–2099. */
    public static function fromName(string $name, array $options): ?array {
        $o = self::validate($options);
        $patterns = [
            'yyyymmdd' => '((?:19|20)\d{2})(\d{2})(\d{2})',
            'ymd' => '((?:19|20)\d{2})[-_.](\d{2})[-_.](\d{2})',
            'dmy' => '(\d{2})[-_.](\d{2})[-_.]((?:19|20)\d{2})',
            'yymmdd' => '(\d{2})(\d{2})(\d{2})',
            'year' => '((?:19|20)\d{2})',
        ];
        $formats = $o['format'] === 'auto' ? array_keys($patterns) : [$o['format']];
        $found = []; $hasDateSyntax = false;
        foreach ($formats as $format) {
            if ($format === 'year' && $o['format'] === 'auto' && $hasDateSyntax) { continue; }
            $prefix = $o['position'] === 'start' ? '^' : '(?<!\d)';
            preg_match_all('~' . $prefix . $patterns[$format] . '(?!\d)~', trim($name), $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                if ($format === 'year') { $found[$m[1]] = ['date' => null, 'year' => $m[1]]; continue; }
                $hasDateSyntax = true;
                [$y, $mo, $d] = $format === 'dmy' ? [$m[3], $m[2], $m[1]] : [$m[1], $m[2], $m[3]];
                if ($format === 'yymmdd') { $y = 2000 + (int)$y; }
                if (!checkdate((int)$mo, (int)$d, (int)$y)) { continue; }
                $date = sprintf('%04d-%02d-%02d', (int)$y, (int)$mo, (int)$d);
                $found[$date] = ['date' => $date, 'year' => (string)$y];
            }
        }
        return count($found) === 1 ? array_values($found)[0] : null;
    }
    public static function fromPath(string $path, array $options = []): ?array {
        $o = self::validate($options);
        $parts = array_reverse(explode('/', trim($path, '/')));
        if (!$o['ancestors']) { $parts = array_slice($parts, 0, 1); }
        foreach ($parts as $name) {
            $match = self::fromName($name, $o);
            if ($match !== null) { return $match; }
        }
        return null;
    }
    /** Multiple event days in one year: earliest day sorts the album; conflicting years stay unknown. */
    public static function forAlbums(array $sources, array $options = []): array {
        $o = self::validate($options); $candidates = [];
        foreach ($sources as $row) {
            $candidates[(int)$row['album_id']][] = $o['source'] === 'album'
                ? self::fromName((string)$row['name'], $o) : self::fromPath((string)$row['path'], $o);
        }
        $result = [];
        foreach ($candidates as $id => $matches) {
            if (in_array(null, $matches, true)) { $result[$id] = ['date' => null, 'year' => null]; continue; }
            $years = array_unique(array_column($matches, 'year'));
            if (count($years) !== 1) { $result[$id] = ['date' => null, 'year' => null]; continue; }
            $dates = array_column($matches, 'date');
            $result[$id] = ['year' => (string)reset($years), 'date' => in_array(null, $dates, true) ? null : min($dates)];
        }
        return $result;
    }
}
