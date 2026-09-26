<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-only
namespace OCA\PhotoGallery\Controller;

use OCA\PhotoGallery\AppInfo\Application;
use OCA\PhotoGallery\Service\Catalog;
use OCA\PhotoGallery\Service\Settings;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

final class ApiController extends Controller {
    public function __construct(IRequest $request, private Settings $settings,
        private Catalog $catalog, private LoggerInterface $logger) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    public function album(int $id, int $after = 0): JSONResponse {
        return $this->respond(fn (array $s): ?array => $this->catalog->getAlbum($s, $id, $after));
    }

    private function respond(callable $getData): JSONResponse {
        $s = $this->settings->get();
        $data = null;
        $status = 404;
        if ($s['enabled']) {
            try {
                $data = $getData($s);
                $status = $data === null ? 404 : 200;
            } catch (\InvalidArgumentException $e) {
                $status = 400;
                $data = ['message' => 'Ungültige Jahresauswahl.'];
            } catch (\Throwable $e) {
                $status = 503;
                $this->logger->error('Public gallery API unavailable', ['app' => Application::APP_ID, 'exception' => $e]);
            }
        }
        $response = new JSONResponse($data ?? ['message' => $status === 404
            ? 'Galerie oder Album ist nicht öffentlich verfügbar.' : 'Die Galerie ist vorübergehend nicht verfügbar.'], $status);
        $response->addHeader('Cache-Control', 'no-store, max-age=0');
        $response->addHeader('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
