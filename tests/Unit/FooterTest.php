<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Stephan Rickauer
// SPDX-License-Identifier: AGPL-3.0-only

use OCA\PhotoGallery\Service\Footer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FooterTest extends TestCase {
    public function testSafeFormattingAndLinksArePreserved(): void {
        self::assertSame(
            '<p>Photos &amp; videos <strong>2026</strong><br><a href="https://example.org/" rel="noopener noreferrer">Visit</a></p>',
            Footer::clean('<p>Photos &amp; videos <strong>2026</strong><br><a href="https://example.org/">Visit</a></p>')
        );
    }

    public function testActiveContentAndAttributesAreRemoved(): void {
        self::assertSame(
            '<p>Safe</p>',
            Footer::clean('<p onclick="alert(1)" style="color:red">Safe<script>alert(1)</script><iframe src="https://example.org/"></iframe><img src="x" onerror="alert(1)"></p>')
        );
    }

    #[DataProvider('unsafeLinks')]
    public function testUnsafeLinkTargetsAreRemoved(string $href): void {
        self::assertSame('<a>Visit</a>', Footer::clean('<a href="' . $href . '">Visit</a>'));
    }

    public static function unsafeLinks(): array {
        return [
            'script' => ['javascript:alert(1)'],
            'encoded script' => ['java&#x73;cript:alert(1)'],
            'data URL' => ['data:text/html,test'],
            'protocol relative' => ['//example.org/'],
        ];
    }

    public function testEmptyFooterStaysEmpty(): void {
        self::assertSame('', Footer::clean('  '));
    }

    public function testOversizedFooterIsRejected(): void {
        $this->expectException(InvalidArgumentException::class);
        Footer::clean(str_repeat('x', 16001));
    }
}
