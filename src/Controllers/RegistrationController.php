<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Exhibitor;
use App\Models\ExhibitorFestival;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Services\CaptchaService;

class RegistrationController
{
     private Environment      $twig;
     private Exhibitor        $exhibitor;
     private ExhibitorFestival $exhibitorFestival;
     private CaptchaService $captcha;

     public function __construct(
          Environment       $twig,
          Exhibitor         $exhibitor,
          ExhibitorFestival $exhibitorFestival,
          CaptchaService    $captcha
     ) {
          $this->twig              = $twig;
          $this->exhibitor         = $exhibitor;
          $this->exhibitorFestival = $exhibitorFestival;
          $this->captcha           = $captcha;
     }

     private function getLangSwitchUrl(string $locale, string $currentPath): string
     {
          $basePath = getenv('APP_BASE_PATH') ?: '';

          if ($locale === 'cs') {
               return $basePath . '/en/registration';
          }

          return $basePath . '/cs/registrace';
     }

     public function showForm(Request $request, Response $response): Response
     {
          $locale = $_SESSION['locale'] ?? 'cs';
          $path   = $request->getUri()->getPath();

          $html = $this->twig->render('registration/form.twig', [
               'locale'          => $locale,
               'fields'          => require __DIR__ . '/../../config/fields.php',
               'festivals'       => require __DIR__ . '/../../config/festivals.php',
               'errors'          => [],
               'old'             => [],
               'lang_switch_url' => $this->getLangSwitchUrl($locale, $path),
          ]);

          $response->getBody()->write($html);
          return $response;
     }

     public function terms(Request $request, Response $response): Response
     {
          $locale = $_SESSION['locale'] ?? 'cs';

          $html = $this->twig->render('registration/terms.twig', [
               'locale' => $locale,
          ]);

          $response->getBody()->write($html);
          return $response;
     }

     public function handleForm(Request $request, Response $response): Response
     {
          $locale = $_SESSION['locale'] ?? 'cs';
          $body   = (array) $request->getParsedBody();

          $token = trim($body['recaptcha_token'] ?? '');
          if (!$this->captcha->verify($token)) {
               $langFile = require __DIR__ . "/../../lang/{$locale}/registration.php";
               $errors   = ['captcha' => $langFile['errors']['captcha']];

               $html = $this->twig->render('registration/form.twig', [
                    'locale'    => $locale,
                    'fields'    => require __DIR__ . '/../../config/fields.php',
                    'festivals' => require __DIR__ . '/../../config/festivals.php',
                    'errors'    => $errors,
                    'old'       => $body,
               ]);
               $response->getBody()->write($html);
               return $response->withStatus(422);
          }

          $errors = $this->validate($body, $locale);

          if (!empty($errors)) {
               // Vrátíme formulář s chybami a starými hodnotami
               $html = $this->twig->render('registration/form.twig', [
                    'locale'    => $locale,
                    'fields'    => require __DIR__ . '/../../config/fields.php',
                    'festivals' => require __DIR__ . '/../../config/festivals.php',
                    'errors'    => $errors,
                    'old'       => $body,
               ]);
               $response->getBody()->write($html);
               return $response->withStatus(422);
          }

          // Uložení do DB
          try {
               $exhibitorId = $this->exhibitor->create([
                    'ico'             => trim($body['ico']             ?? ''),
                    'company'         => trim($body['company']         ?? ''),
                    'address'         => trim($body['address']         ?? ''),
                    'dic'             => trim($body['dic']             ?? '') ?: null,
                    'contact_name'    => trim($body['contact_name']    ?? ''),
                    'email'           => trim($body['email']           ?? ''),
                    'phone'           => trim($body['phone']           ?? ''),
                    'website'         => trim($body['website']         ?? '') ?: null,
                    'social_networks' => trim($body['social_networks'] ?? '') ?: null,
                    'sortiment'       => trim($body['sortiment']       ?? ''),
                    'ip_address'      => $this->getClientIp($request),
               ]);

               // Uložení vazby na festivaly
               $festivalIds = $body['festivals'] ?? [];
               if (!empty($festivalIds)) {
                    $this->exhibitorFestival->saveMany($exhibitorId, $festivalIds);
               }
          } catch (\Throwable $e) {
               // Logování chyby
               error_log('Registration save error: ' . $e->getMessage());

               $errors['_db'] = $locale === 'cs'
                    ? 'Nastala chyba při ukládání. Zkuste to prosím znovu.'
                    : 'An error occurred while saving. Please try again.';

               $html = $this->twig->render('registration/form.twig', [
                    'locale'    => $locale,
                    'fields'    => require __DIR__ . '/../../config/fields.php',
                    'festivals' => require __DIR__ . '/../../config/festivals.php',
                    'errors'    => $errors,
                    'old'       => $body,
               ]);
               $response->getBody()->write($html);
               return $response->withStatus(500);
          }

          // Úspěch — redirect aby nešlo formulář odeslat znovu přes F5
          $successUrl = $locale === 'cs'
               ? '/vystavovatele/cs/dekujeme'
               : '/vystavovatele/en/thank-you';

          return $response
               ->withHeader('Location', $successUrl)
               ->withStatus(302);
     }

     public function success(Request $request, Response $response): Response
     {
          $html = $this->twig->render('registration/success.twig', [
               'locale' => $_SESSION['locale'] ?? 'cs',
          ]);

          $response->getBody()->write($html);
          return $response;
     }

     // ── Validace ──────────────────────────────────────────────────────────

     private function validate(array $data, string $locale): array
     {
          $errors   = [];
          $langFile = require __DIR__ . "/../../lang/{$locale}/registration.php";
          $e        = $langFile['errors'];

          // Povinná textová pole
          $required = [
               'company',
               'address',
               'contact_name',
               'email',
               'phone',
               'sortiment',
          ];

          foreach ($required as $field) {
               if (empty(trim($data[$field] ?? ''))) {
                    $errors[$field] = $e[$field];
               }
          }

          // IČ — jen pokud je vyplněno (není povinné strukturálně, ale biz. logika)
          if (!empty($data['ico'])) {
               $ico = preg_replace('/\D/', '', $data['ico']);
               if (strlen($ico) !== 8) {
                    $errors['ico'] = $locale === 'cs'
                         ? 'IČ musí mít 8 číslic'
                         : 'Company ID must be 8 digits';
               }
          }

          // E-mail formát
          if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
               $errors['email'] = $e['email'];
          }

          // Festivaly — alespoň jeden
          $festivals      = $data['festivals'] ?? [];
          $validFestivals = array_column(require __DIR__ . '/../../config/festivals.php', 'id');

          if (empty($festivals)) {
               $errors['festivals'] = $e['festivals'];
          } else {
               // Ověříme že odeslané ID existují v configu (ochrana před manipulací)
               foreach ($festivals as $fid) {
                    if (!in_array((int) $fid, $validFestivals, true)) {
                         $errors['festivals'] = $e['festivals'];
                         break;
                    }
               }
          }

          // Souhlas s podmínkami
          if (empty($data['terms'])) {
               $errors['terms'] = $e['terms'];
          }

          return $errors;
     }

     // ── Helper — IP adresa ────────────────────────────────────────────────

     private function getClientIp(Request $request): string
     {
          $headers = [
               'HTTP_CLIENT_IP',
               'HTTP_X_FORWARDED_FOR',
               'REMOTE_ADDR',
          ];

          $serverParams = $request->getServerParams();

          foreach ($headers as $header) {
               if (!empty($serverParams[$header])) {
                    $ip = trim(explode(',', $serverParams[$header])[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                         return $ip;
                    }
               }
          }

          return '0.0.0.0';
     }
}
