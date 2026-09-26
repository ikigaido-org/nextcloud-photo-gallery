<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Service;

use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\App\IAppManager;

/** Validated presentation settings; no arbitrary CSS or server-side remote fetches. */
final class Appearance {
    public const COLORS = [
        'backgroundColor' => '#ffffff', 'titleColor' => '#242823',
        'subtitleColor' => '#242823', 'textColor' => '#242823',
        'yearColor'=>'#242823', 'navigationColor'=>'#242823', 'footerColor'=>'#242823',
        'cardColor' => '#ffffff', 'albumTitleColor' => '#242823',
        'locationColor' => '#555555', 'countColor' => '#555555',
        'filterColor' => '#f5f5f2', 'filterTextColor' => '#242823', 'filterBorderColor' => '#dddddd',
        'filterActiveColor' => '#242823', 'filterActiveTextColor' => '#ffffff', 'filterActiveBorderColor' => '#242823',
    ];
    public function __construct(private IURLGenerator $urls, private IConfig $config, private IAppManager $apps) {}

    public static function defaults(): array {
        return ['enabled' => false, 'headingFontUrl' => '', 'backgroundMode' => 'color', 'backgroundImage' => '', 'overlay' => 35, 'filterRadius' => 6, 'captionMode'=>'overlay', 'captionOpacity'=>65] + self::COLORS;
    }

    public function validate(array $input): array {
        $result = array_replace(self::defaults(), array_intersect_key($input, self::defaults()));
        $enabled = filter_var($result['enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) { throw new \InvalidArgumentException('Ungültige Darstellungseinstellung.'); }
        $result['enabled'] = $enabled;
        if (!is_string($result['headingFontUrl'])) { throw new \InvalidArgumentException('Ungültiger Schriftlink.'); }
        $result['headingFontUrl'] = $this->normalizeFontUrl(trim($result['headingFontUrl']));
        foreach (self::COLORS as $key => $default) {
            if (!is_string($result[$key]) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $result[$key])) {
                throw new \InvalidArgumentException('Farben bitte als sechsstelligen HEX-Wert angeben, beispielsweise #ffffff.');
            }
            $result[$key] = strtolower($result[$key]);
        }
        if (!in_array($result['backgroundMode'], ['color', 'image', 'nextcloud'], true)) {
            throw new \InvalidArgumentException('Ungültiger Hintergrundtyp.');
        }
        $overlay = filter_var($result['overlay'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        if ($overlay === false) { throw new \InvalidArgumentException('Die Hintergrundüberlagerung muss zwischen 0 und 100 Prozent liegen.'); }
        $result['overlay'] = $overlay;
        $radius = filter_var($result['filterRadius'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 30]]);
        if ($radius === false) { throw new \InvalidArgumentException('Filter-Rundung: 0–30 Pixel.'); }
        $result['filterRadius'] = $radius;
        if(!in_array($result['captionMode'],['overlay','below'],true)){throw new \InvalidArgumentException('Ungültige Kartenbeschriftung.');}
        $opacity=filter_var($result['captionOpacity'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]);
        if($opacity===false){throw new \InvalidArgumentException('Beschriftungs-Deckkraft: 0–100 Prozent.');}
        $result['captionOpacity']=$opacity;
        if (!is_string($result['backgroundImage'])) { throw new \InvalidArgumentException('Ungültiger Bildlink.'); }
        $result['backgroundImage'] = $this->normalizeImageUrl(trim($result['backgroundImage']));
        if ($result['enabled'] && $result['backgroundMode'] === 'image' && $result['backgroundImage'] === '') {
            throw new \InvalidArgumentException('Bitte einen öffentlichen Nextcloud-Link auf die Hintergrund-Bilddatei eintragen.');
        }
        return $result;
    }

    private function normalizeFontUrl(string $url): string {
        if ($url === '') { return ''; }
        $parts = parse_url($url);
        if (strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false || $parts === false
            || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || preg_match('/[\x00-\x20\x7f]/', $url) || strpbrk($url, "\"'\\<>") !== false
            || !preg_match('/\.woff2$/iD', $parts['path'] ?? '')) {
            throw new \InvalidArgumentException('Bitte einen direkten HTTPS-Link auf eine WOFF2-Schriftdatei angeben, ohne Zugangsdaten oder Fragment.');
        }
        return $url;
    }

    public function fontOrigin(array $input): string {
        try { $a = $this->validate($input); } catch (\InvalidArgumentException) { return ''; }
        if ($a['headingFontUrl'] === '') { return ''; }
        $parts = parse_url($a['headingFontUrl']);
        return 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    private function normalizeImageUrl(string $url): string {
        if ($url === '') { return ''; }
        $base = parse_url($this->urls->getAbsoluteURL('/'));
        $parts = parse_url($url);
        $valid = strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL) !== false && $parts !== false
            && ($parts['scheme'] ?? '') === 'https'
            && strtolower($parts['host'] ?? '') === strtolower($base['host'] ?? '')
            && ($parts['port'] ?? 443) === ($base['port'] ?? 443)
            && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['query']) && !isset($parts['fragment']);
        // Only native public FILE share routes on this Nextcloud, not internal file links,
        // arbitrary paths, folder selections, third-party images or CSS payloads.
        $prefix = rtrim($base['path'] ?? '', '/');
        if (!$valid
            || !preg_match('~^' . preg_quote($prefix, '~') . '/(?:index\.php/)?s/([A-Za-z0-9]{6,128})(?:/download)?/?$~D', $parts['path'] ?? '', $m)) {
            throw new \InvalidArgumentException('Bitte einen öffentlichen Datei-Link dieser Nextcloud verwenden (https://…/s/…), ohne Passwort, Zusatzparameter oder Ordnerauswahl.');
        }
        // Keep the supplied native /index.php prefix for installations without clean URLs.
        return preg_replace('~/download/?$~', '', rtrim($url, '/')) . '/download';
    }

    public function css(array $input): string {
        try { $a = $this->validate($input); } catch (\InvalidArgumentException) { return ''; }
        $caption=':root{--pg-caption-alpha:'.($a['captionOpacity']/100).';}';
        if ($a['headingFontUrl'] !== '') {
            $caption .= '@font-face{font-family:"PhotoGalleryHeading";src:url("' . $a['headingFontUrl']
                . '") format("woff2");font-weight:400;font-style:normal;font-display:swap;}'
                . '.mpi-gallery h1{font-family:"PhotoGalleryHeading","Work Sans",sans-serif;}';
        }
        if (!$a['enabled']) { return $caption; }
        $imageUrl = $a['backgroundMode'] === 'image' ? $a['backgroundImage'] : '';
        if ($a['backgroundMode'] === 'nextcloud') {
            $theme = $this->nextcloudBackground();
            $imageUrl = $theme['url'];
            // With no image, use Nextcloud's global background colour directly.
            if ($imageUrl === '' && $theme['color'] !== '') { $a['backgroundColor'] = $theme['color']; }
        }
        $mapping = ['backgroundColor' => 'background', 'titleColor' => 'title',
            'subtitleColor' => 'subtitle', 'textColor' => 'text',
            'yearColor'=>'year', 'navigationColor'=>'navigation', 'footerColor'=>'footer', 'cardColor' => 'card',
            'albumTitleColor' => 'album-title', 'locationColor' => 'location', 'countColor' => 'count',
            'filterColor'=>'filter', 'filterTextColor'=>'filter-text', 'filterBorderColor'=>'filter-border',
            'filterActiveColor'=>'filter-active', 'filterActiveTextColor'=>'filter-active-text', 'filterActiveBorderColor'=>'filter-active-border'];
        $css = ':root{color-scheme:light;';
        foreach ($mapping as $key => $variable) { $css .= '--mpi-' . $variable . ':' . $a[$key] . ';'; }
        $css .= '--mpi-filter-radius:' . $a['filterRadius'] . 'px;}';
        if ($imageUrl !== '') {
            // Keep configured same-instance backgrounds on the gallery proxy origin.
            $parts=parse_url($imageUrl);$base=parse_url($this->urls->getAbsoluteURL('/'));
            if(isset($parts['host']) && strtolower($parts['host'])===strtolower($base['host']??'')){
                $imageUrl=($parts['path']??'/').(isset($parts['query'])?'?'.$parts['query']:'');
            }
            $shade = $a['backgroundColor'] . sprintf('%02x', (int)round($a['overlay'] * 255 / 100));
            $imageUrl = str_replace(['\\', '"', '<', '>', "\n", "\r"], ['%5C', '%22', '%3C', '%3E', '', ''], $imageUrl);
            $css .= 'body{background-image:linear-gradient(' . $shade . ',' . $shade . '),url("'
                . $imageUrl . '");background-size:cover;background-position:center top;background-attachment:fixed;}'
                . '@media(max-width:540px),(hover:none){body{background-attachment:scroll;}}';
        }
        return $caption.$css;
    }

    /** NC33 global ImageManager policy, using public routes and config only. No user background. */
    private function nextcloudBackground(): array {
        if (!$this->apps->isEnabledForAnyone('theming')) { return ['url' => '', 'color' => '']; }
        $color = $this->config->getAppValue('theming', 'background_color', '#00679e');
        if (preg_match('/^#([0-9a-f]{3})$/iD', $color, $m)) {
            $color = '#' . $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2];
        }
        if (!preg_match('/^#[0-9a-f]{6}$/iD', $color)) { $color = '#00679e'; }
        $mime = $this->config->getAppValue('theming', 'backgroundMime', '');
        if ($mime === 'backgroundColor') { return ['url' => '', 'color' => $color]; }
        if ($mime !== '') {
            $url = $this->urls->linkToRoute('theming.Theming.getImage', ['key' => 'background'])
                . '?v=' . rawurlencode($this->config->getAppValue('theming', 'cachebuster', '0'));
        } else {
            // BackgroundService::DEFAULT_BACKGROUND_IMAGE in supported Nextcloud 33.
            $url = $this->urls->linkTo('theming', 'img/background/jo-myoung-hee-fluid.webp');
        }
        return ['url' => $url, 'color' => $color];
    }
}
