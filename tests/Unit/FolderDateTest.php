<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only

use OCA\PhotoGallery\Service\FolderDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FolderDateTest extends TestCase {
    #[DataProvider('names')]
    public function testEventDates(string $name, ?array $expected): void {
        self::assertSame($expected, FolderDate::fromName($name, []));
    }

    public static function names(): array {
        $date = ['date' => '2026-09-26', 'year' => '2026'];
        return [
            'short compact' => ['260926_Event', $date],
            'long compact' => ['20260926_Event', $date],
            'ISO date' => ['2026-09-26_Event', $date],
            'European date' => ['26.09.2026_Event', $date],
            'year without invented day' => ['Events 2026', ['date' => null, 'year' => '2026']],
            'valid leap day' => ['2024-02-29_Event', ['date' => '2024-02-29', 'year' => '2024']],
            'invalid leap day' => ['2026-02-29_Event', null],
            'ambiguous dates' => ['2026-09-25_2026-09-26', null],
            'no date' => ['Undated event', null],
        ];
    }

    public function testAncestorSearchCanBeDisabled(): void {
        self::assertSame(['date' => '2026-09-26', 'year' => '2026'], FolderDate::fromPath('/2026-09-26_Event/photos'));
        self::assertNull(FolderDate::fromPath('/2026-09-26_Event/photos', ['ancestors' => false]));
    }

    public function testAlbumDateUsesEarliestDayButRejectsMixedYearsAndMissingDates(): void {
        $rows = [
            ['album_id' => 1, 'path' => '/2026-09-26_Event'],
            ['album_id' => 1, 'path' => '/2026-09-25_Event'],
            ['album_id' => 2, 'path' => '/2025-09-26_Event'],
            ['album_id' => 2, 'path' => '/2026-09-26_Event'],
            ['album_id' => 3, 'path' => '/2026-09-26_Event'],
            ['album_id' => 3, 'path' => '/Undated'],
        ];
        $result = FolderDate::forAlbums($rows);
        self::assertSame(['year' => '2026', 'date' => '2026-09-25'], $result[1]);
        self::assertSame(['date' => null, 'year' => null], $result[2]);
        self::assertSame(['date' => null, 'year' => null], $result[3]);
    }

    public function testAlbumNameCanBeUsedInsteadOfFolderName(): void {
        $result = FolderDate::forAlbums([
            ['album_id' => 1, 'name' => '2026-09-26_Event', 'path' => '/Undated'],
        ], ['source' => 'album']);
        self::assertSame(['year' => '2026', 'date' => '2026-09-26'], $result[1]);
    }
}
