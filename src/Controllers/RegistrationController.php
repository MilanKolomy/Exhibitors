<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Exhibitor;
use App\Models\ExhibitorFestival;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;
use App\Services\CaptchaService;
use App\Services\MailService;

class RegistrationController
{
     private Environment      $twig;
     private Exhibitor        $exhibitor;
     private ExhibitorFestival $exhibitorFestival;
     private CaptchaService $captcha;
     private MailService $mailService;

     public function __construct(
          Environment       $twig,
          Exhibitor         $exhibitor,
          ExhibitorFestival $exhibitorFestival,
          CaptchaService    $captcha,
          MailService       $mailService
     ) {
          $this->twig              = $twig;
          $this->exhibitor         = $exhibitor;
          $this->exhibitorFestival = $exhibitorFestival;
          $this->captcha           = $captcha;
          $this->mailService       = $mailService;
     }

     private function getLangSwitchUrl(string $locale, string $currentPath): string
     {
          $basePath = env('APP_BASE_PATH') ?: '';

          if ($locale === 'cs') {
               return $basePath . '/en/registration';
          }

          return $basePath . '/cs/registrace';
     }

     public function showForm(Request $request, Response $response): Response
     {
          $locale   = $_SESSION['locale'] ?? 'cs';
          $path     = $request->getUri()->getPath();
          $festivals = require __DIR__ . '/../../config/festivals.php';
          $pricing   = require __DIR__ . '/../../config/pricing.php';

          $html = $this->twig->render('registration/form.twig', [
               'locale'          => $locale,
               'fields'          => require __DIR__ . '/../../config/fields.php',
               'festivals'       => $festivals,
               'pricing'         => $pricing,
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

          // reCAPTCHA
          $token = trim($body['recaptcha_token'] ?? '');
          if (!$this->captcha->verify($token)) {
               $langFile = require __DIR__ . "/../../lang/{$locale}/registration.php";
               $errors   = ['captcha' => $langFile['errors']['captcha']];
               return $this->renderForm($response, $locale, $errors, $body);
          }

          // Validace
          $errors = $this->validate($body, $locale);
          if (!empty($errors)) {
               return $this->renderForm($response, $locale, $errors, $body, 422);
          }

          // Festivaly z JSON
          $festivalsData = json_decode($body['festivals_data'] ?? '{}', true) ?? [];

          try {
               // Celková cena
               $totalPrice = 0;
               foreach ($festivalsData as $fData) {
                    $totalPrice += (int)($fData['priceTotal'] ?? 0);
               }

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
                    'total_price'     => $totalPrice,
                    'ip_address'      => $this->getClientIp($request),
               ]);

               // Uložení festivalů
               if (!empty($festivalsData)) {
                    $this->exhibitorFestival->saveMany($exhibitorId, $festivalsData);
               }

               // Potvrzovací e-mail
               $festivals  = require __DIR__ . '/../../config/festivals.php';
               $pricing    = require __DIR__ . '/../../config/pricing.php';
               $festivalMap = array_column($festivals, null, 'id');

               $festivalRows = [];
               foreach ($festivalsData as $fid => $fData) {
                    $festival = $festivalMap[(int)$fid] ?? null;
                    if (!$festival) continue;

                    $festivalRows[] = [
                         'city'        => $festival['city'],
                         'name'        => $festival['name'],
                         'date_label'  => $this->formatDateRange(
                              $festival['date_from'],
                              $festival['date_to']
                         ),
                         'type'        => $festival['type'],
                         'space_label' => $this->getSpaceLabel(
                              $festival['type'],
                              $fData['space'] ?? '',
                              $pricing
                         ),
                         'elec_label'  => $this->getElecLabel(
                              $festival['type'],
                              $fData['electricity'] ?? '',
                              $pricing
                         ),
                         'price_total' => (int)($fData['priceTotal'] ?? 0),
                    ];
               }

               $exhibitorData = array_merge(
                    (array) $body,
                    ['locale' => $locale]
               );

               $this->mailService->sendConfirmation($exhibitorData, $festivalRows, $pricing);
          } catch (\Throwable $e) {
               error_log('Registration error: ' . $e->getMessage());
               $errors['_db'] = $locale === 'cs'
                    ? 'Nastala chyba při ukládání. Zkuste to prosím znovu.'
                    : 'An error occurred. Please try again.';
               return $this->renderForm($response, $locale, $errors, $body, 500);
          }

          $successUrl = $locale === 'cs'
               ? basePath('cs/dekujeme')
               : basePath('en/thank-you');

          return $response->withHeader('Location', $successUrl)->withStatus(302);
     }

     // ── Helpery ──────────────────────────────────────────────────────────────

     private function renderForm(
          Response $response,
          string   $locale,
          array    $errors,
          array    $old,
          int      $status = 422
     ): Response {
          $html = $this->twig->render('registration/form.twig', [
               'locale'           => $locale,
               'fields'           => require __DIR__ . '/../../config/fields.php',
               'festivals'        => require __DIR__ . '/../../config/festivals.php',
               'pricing'          => require __DIR__ . '/../../config/pricing.php',
               'errors'           => $errors,
               'old'              => $old,
               'old_festivals'    => $old['festivals_data'] ?? '{}', // ← přidat
               'lang_switch_url'  => $this->getLangSwitchUrl($locale, ''),
          ]);
          $response->getBody()->write($html);
          return $response->withStatus($status);
     }

     private function formatDateRange(string $from, string $to): string
     {
          $f = new \DateTime($from);
          $t = new \DateTime($to);

          if ($f->format('m') === $t->format('m')) {
               return $f->format('j') . '–' . $t->format('j. n. Y');
          }
          return $f->format('j. n.') . ' – ' . $t->format('j. n. Y');
     }

     private function getSpaceLabel(string $type, string $spaceId, array $pricing): string
     {
          foreach ($pricing['spaces'][$type] ?? [] as $s) {
               if ($s['id'] === $spaceId) return $s['label'];
          }
          return $spaceId;
     }

     private function getElecLabel(string $type, string $elecId, array $pricing): string
     {
          foreach ($pricing['electricity'][$type] ?? [] as $e) {
               if ($e['id'] === $elecId) return $e['label'];
          }
          return $elecId;
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

          // IČ — jen pokud je vyplněno
          if (!empty($data['ico'])) {
               $ico = preg_replace('/\D/', '', $data['ico']);
               if (strlen($ico) !== 8) {
                    $errors['ico'] = $locale === 'cs'
                         ? 'IČ musí mít 8 číslic'
                         : 'Company ID must be 8 digits';
               }
          }

          // E-mail formát — stejný regex jako Alpine.js
          if (!empty($data['email'])) {
               if (
                    !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
                    || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $data['email'])
               ) {
                    $errors['email'] = $e['email'];
               }
          }

          // ── Festivaly — čteme z festivals_data (JSON) ──────────────────────
          $festivalsData = json_decode($data['festivals_data'] ?? '{}', true) ?? [];
          $validFestivals = array_column(
               require __DIR__ . '/../../config/festivals.php',
               'id'
          );

          if (empty($festivalsData)) {
               $errors['festivals'] = $e['festivals'];
          } else {
               // Ověř že každý festival má vybraný prostor a elektřinu
               foreach ($festivalsData as $fid => $fData) {
                    if (!in_array((int) $fid, $validFestivals, true)) {
                         $errors['festivals'] = $e['festivals'];
                         break;
                    }
                    if (empty($fData['space']) || !isset($fData['electricity'])) {
                         $errors['festivals'] = $locale === 'cs'
                              ? 'U každého festivalu musí být vybrán prostor a elektrické připojení'
                              : 'Each festival requires a space and electricity selection';
                         break;
                    }
               }
          }

          // ── Souhlas s podmínkami ───────────────────────────────────────────
          // Checkbox posílá '1' pokud zaškrtnut, jinak pole vůbec neexistuje
          if (empty($data['terms']) || $data['terms'] !== '1') {
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
