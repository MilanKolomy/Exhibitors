<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Exhibitor;
use App\Services\ExportService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class DashboardController
{
    private Environment   $twig;
    private Exhibitor     $exhibitor;
    private ExportService $exportService;

    public function __construct(
        Environment   $twig,
        Exhibitor     $exhibitor,
        ExportService $exportService
    ) {
        $this->twig          = $twig;
        $this->exhibitor     = $exhibitor;
        $this->exportService = $exportService;
    }

    public function index(Request $request, Response $response): Response
    {
        $festivals  = require __DIR__ . '/../../../config/festivals.php';
        $params     = $request->getQueryParams();
        $festivalId = isset($params['festival']) && $params['festival'] !== ''
            ? (int) $params['festival']
            : null;

        $exhibitors = $this->exhibitor->getAll($festivalId);

        $html = $this->twig->render('admin/dashboard.twig', [
            'locale'          => 'cs',
            'exhibitors'      => $exhibitors,
            'festivals'       => $festivals,
            'selected_festival' => $festivalId,
            'total'           => count($exhibitors),
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function export(Request $request, Response $response): Response
    {
        $festivals  = require __DIR__ . '/../../../config/festivals.php';
        $params     = $request->getQueryParams();
        $festivalId = isset($params['festival']) && $params['festival'] !== ''
            ? (int) $params['festival']
            : null;

        $exhibitors = $this->exhibitor->getAll($festivalId);
        $tmpFile    = $this->exportService->generateXlsx($exhibitors, $festivals);

        $filename   = 'registrace_' . date('Y-m-d_His') . '.xlsx';

        // Streamování souboru ke stažení
        $response = $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withHeader('Content-Length', (string) filesize($tmpFile))
            ->withHeader('Cache-Control', 'no-store');

        $response->getBody()->write(file_get_contents($tmpFile));

        // Smazání temp souboru
        unlink($tmpFile);

        return $response;
    }
}