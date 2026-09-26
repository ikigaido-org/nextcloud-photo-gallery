<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only

use OCA\PhotoGallery\Service\PublicUrls;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicUrlsTest extends TestCase {
    #[DataProvider('origins')]
    public function testOnlyHttpsOriginsAreAccepted(string $url, bool $expected): void {
        self::assertSame($expected, PublicUrls::validOrigin($url));
    }

    public static function origins(): array {
        return [
            'HTTPS host' => ['https://gallery.example.org', true],
            'HTTPS with port' => ['https://gallery.example.org:8443', true],
            'HTTP' => ['http://gallery.example.org', false],
            'credentials' => ['https://user:password@gallery.example.org', false],
            'path' => ['https://gallery.example.org/albums', false],
            'query' => ['https://gallery.example.org?album=1', false],
            'fragment' => ['https://gallery.example.org#album', false],
            'not a URL' => ['gallery.example.org', false],
        ];
    }

    public function testReadableSlugsAndReservedRoutes(): void {
        self::assertSame('zuerich-sommerfest', PublicUrls::slug('Zürich: Sommerfest!'));
        self::assertSame('album-login', PublicUrls::slug('Login'));
        self::assertSame('album', PublicUrls::slug('!!!'));
    }

    public function testDuplicateNamesDoNotCollideWithExistingSuffixedNames(): void {
        $rows = [
            ['album_id' => 1, 'name' => 'Summer'],
            ['album_id' => 2, 'name' => 'Summer'],
            ['album_id' => 3, 'name' => 'Summer-1'],
        ];
        $slugs = PublicUrls::slugs($rows);
        self::assertCount(3, array_unique($slugs));
        self::assertSame('summer-1', $slugs[3]);
        $reversed = PublicUrls::slugs(array_reverse($rows));
        foreach ($slugs as $id => $slug) {
            self::assertSame($slug, $reversed[$id], 'Album URLs must not depend on listing order.');
        }
    }
}
