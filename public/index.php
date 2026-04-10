<?php

declare(strict_types=1);

require __DIR__ . '/../config/settings.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/helpers.php';

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Middleware\LocaleMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

// Lokální vývoj v subdirectory
$app->setBasePath('/vystavovatele');

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(
     (bool) getenv('APP_DEBUG'),
     true,
     true
);

// Custom 404 handler
$errorMiddleware->setErrorHandler(
     HttpNotFoundException::class,
     function (ServerRequestInterface $request) use ($container): Response {
          $locale = $_SESSION['locale'] ?? 'cs';
          $twig   = $container->get(\Twig\Environment::class);

          $html = $twig->render('errors/404.twig', [
               'locale' => $locale,
          ]);

          $response = new Response();
          $response->getBody()->write($html);
          return $response->withStatus(404);
     }
);
$app->add(LocaleMiddleware::class);

(require __DIR__ . '/../config/routes.php')($app);

$app->run();
