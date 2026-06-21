<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

class AuthController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function showLogin(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Už přihlášen — přesměruj na dashboard
        if (!empty($_SESSION['admin_logged_in'])) {
            return $response
                ->withHeader('Location', basePath('admin/'))
                ->withStatus(302);
        }

        $html = $this->twig->render('admin/login.twig', [
            'error'  => $_SESSION['admin_error'] ?? null,
            'locale' => 'cs',
        ]);

        unset($_SESSION['admin_error']);

        $response->getBody()->write($html);
        return $response;
    }

    public function handleLogin(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $body = (array) $request->getParsedBody();
        $user = trim($body['username'] ?? '');
        $pass = trim($body['password'] ?? '');

        $validUser = env('ADMIN_USER');
        $validHash = env('ADMIN_PASS_HASH');

        if ($user === $validUser && password_verify($pass, $validHash)) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user']      = $user;

            return $response
                ->withHeader('Location', basePath('admin/'))
                ->withStatus(302);
        }

        // Nesprávné údaje — zpět na login s chybou
        $_SESSION['admin_error'] = __t('admin.login_error', [], 'cs');

        return $response
            ->withHeader('Location', basePath('admin/login'))
            ->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();

        return $response
            ->withHeader('Location', basePath('admin/login'))
            ->withStatus(302);
    }
}
