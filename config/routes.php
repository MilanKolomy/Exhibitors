<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\RegistrationController;
use App\Controllers\AresController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Middleware\AdminAuthMiddleware;

return function (App $app): void {

    // --- Přesměrování z / na výchozí jazyk ---
    $app->get('/', function ($request, $response) {
        return $response
          ->withHeader('Location', '/Exhibitors/cs/registrace')
          ->withStatus(302);
    });

    // --- Veřejná část — CS ---
    $app->group('/cs', function (RouteCollectorProxy $group) {
        $group->get('/registrace',  [RegistrationController::class, 'showForm']);
        $group->post('/registrace', [RegistrationController::class, 'handleForm']);
        $group->get('/dekujeme',    [RegistrationController::class, 'success']);
        $group->get('/podminky', [RegistrationController::class, 'terms']);
    });

    // --- Veřejná část — EN ---
    $app->group('/en', function (RouteCollectorProxy $group) {
        $group->get('/registration',  [RegistrationController::class, 'showForm']);
        $group->post('/registration', [RegistrationController::class, 'handleForm']);
        $group->get('/thank-you',     [RegistrationController::class, 'success']);
        $group->get('/terms', [RegistrationController::class, 'terms']);
    });

    // --- AJAX endpoint pro ARES ---
    $app->get('/api/ares/{ico}', [AresController::class, 'lookup']);

    // --- Administrace ---
    $app->group('/admin', function (RouteCollectorProxy $group) {

        // Přihlášení (bez middleware)
        $group->get( '/login',  [AuthController::class, 'showLogin']);
        $group->post('/login',  [AuthController::class, 'handleLogin']);
        $group->get( '/logout', [AuthController::class, 'logout']);

        // Chráněná část
        $group->group('', function (RouteCollectorProxy $protected) {
            $protected->get( '/',       [DashboardController::class, 'index']);
            $protected->get( '/export', [DashboardController::class, 'export']);
        })->add(AdminAuthMiddleware::class);
    });
};