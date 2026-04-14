<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    private const SUPPORTED = ['cs', 'en'];
    private const DEFAULT   = 'cs';

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $path     = $request->getUri()->getPath();
        $segments = explode('/', trim($path, '/'));

        // Hledáme locale kdekoliv v segmentech — ne jen na první pozici
        // {{ base_path }}/en/registration → ['vystavovatele', 'en', 'registration']
        $locale = null;
        foreach ($segments as $segment) {
            if (in_array($segment, self::SUPPORTED, true)) {
                $locale = $segment;
                break;
            }
        }

        if ($locale !== null) {
            $_SESSION['locale'] = $locale;
        }

        if (empty($_SESSION['locale'])) {
            $_SESSION['locale'] = self::DEFAULT;
        }

        return $handler->handle($request);
    }
}
