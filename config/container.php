<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Services\AresService;
use App\Services\CaptchaService;
use App\Services\ExportService;
use App\Models\Exhibitor;
use App\Models\ExhibitorFestival;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\AuthController;
use App\Controllers\RegistrationController;

return [

     // --- Databáze (PDO) ---
     PDO::class => function () {
          $dsn = sprintf(
               'mysql:host=%s;dbname=%s;charset=utf8mb4',
               env('DB_HOST'),
               env('DB_NAME')
          );
          $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
               PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
               PDO::ATTR_EMULATE_PREPARES   => false,
          ]);
          return $pdo;
     },

     // --- Twig ---
     Environment::class => function (ContainerInterface $c) {
          $loader = new FilesystemLoader(__DIR__ . '/../templates');
          $twig   = new Environment($loader, [
               'cache'       => env('APP_ENV') === 'production'
                    ? __DIR__ . '/../var/cache/twig'
                    : false,
               'auto_reload' => true,
          ]);


          // Globální proměnné dostupné ve všech šablonách
          $twig->addGlobal('base_path', rtrim(env('APP_BASE_PATH') ?: '', '/'));
          $twig->addGlobal('app_name', 'Registrace vystavovatelů');
          $twig->addFunction(new \Twig\TwigFunction('current_locale', function () {
               return $_SESSION['locale'] ?? 'cs';
          }));
          $twig->addGlobal('festivals', require __DIR__ . '/festivals.php');

          $twig->addFunction(new \Twig\TwigFunction('t', function (string $key, array $replace = []) {
               $locale = $_SESSION['locale'] ?? 'cs';
               return __t($key, $replace, $locale);
          }));

          $twig->addFunction(new \Twig\TwigFunction('field_label', function (string $field) {
               $locale = $_SESSION['locale'] ?? 'cs';
               return __t("registration.fields.{$field}.label", [], $locale);
          }));

          $twig->addFunction(new \Twig\TwigFunction('field_hint', function (string $field) {
               $locale = $_SESSION['locale'] ?? 'cs';
               return __t("registration.fields.{$field}.hint", [], $locale);
          }));

          $twig->addFunction(new \Twig\TwigFunction('term', function (string $key) {
               $locale = $_SESSION['locale'] ?? 'cs';
               $params = require __DIR__ . "/../lang/{$locale}/terms.php";
               $text   = __t("registration.terms_page.items.{$key}", [], $locale);

               foreach ($params as $param => $value) {
                    $text = str_replace(':' . $param, $value, $text);
               }

               return $text;
          }));

          $captcha = new \App\Services\CaptchaService();
          $twig->addGlobal('recaptcha_enabled',  $captcha->isEnabled());
          $twig->addGlobal('recaptcha_site_key', $captcha->getSiteKey());
          $twig->addGlobal('app_env', env('APP_ENV') ?: 'development');

          return $twig;
     },

     // --- Services ---
     AresService::class    => \DI\autowire(),
     CaptchaService::class => \DI\autowire(),
     ExportService::class  => \DI\autowire(),

     Exhibitor::class => function (ContainerInterface $c) {
          return new Exhibitor($c->get(PDO::class));
     },

     ExhibitorFestival::class => function (ContainerInterface $c) {
          return new ExhibitorFestival($c->get(PDO::class));
     },

     ExportService::class => \DI\autowire(),

     DashboardController::class => function (ContainerInterface $c) {
          return new DashboardController(
               $c->get(\Twig\Environment::class),
               $c->get(\App\Models\Exhibitor::class),
               $c->get(ExportService::class)
          );
     },

     CaptchaService::class => \DI\autowire(),

     RegistrationController::class => function (ContainerInterface $c) {
          return new RegistrationController(
               $c->get(\Twig\Environment::class),
               $c->get(\App\Models\Exhibitor::class),
               $c->get(\App\Models\ExhibitorFestival::class),
               $c->get(\App\Services\CaptchaService::class),
               $c->get(\App\Services\MailService::class)
          );
     },

     \App\Services\MailService::class => \DI\autowire(),
];
