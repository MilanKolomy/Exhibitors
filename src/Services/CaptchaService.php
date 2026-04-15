<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class CaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const MIN_SCORE  = 0.5; // 0.0 (bot) až 1.0 (člověk)

    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 5]);
    }

    public function isEnabled(): bool
    {
        return env('APP_ENV') === 'production'
            && !empty(env('RECAPTCHA_SECRET_KEY'));
    }

    public function getSiteKey(): string
    {
        return env('RECAPTCHA_SITE_KEY') ?: '';
    }

    public function verify(string $token): bool
    {
        // Na localhostu / dev prostředí captchu přeskočíme
        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->client->post(self::VERIFY_URL, [
                'form_params' => [
                    'secret'   => env('RECAPTCHA_SECRET_KEY'),
                    'response' => $token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return isset($data['success'], $data['score'])
                && $data['success'] === true
                && $data['score'] >= self::MIN_SCORE
                && ($data['action'] ?? '') === 'register';

        } catch (GuzzleException $e) {
            error_log('reCAPTCHA verify error: ' . $e->getMessage());
            return false;
        }
    }
}