<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AresService
{
     private Client $client;
     private const API_URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';

     public function __construct()
     {
          $this->client = new Client([
               'timeout'         => 5,
               'connect_timeout' => 5,
               'verify'          => getenv('APP_ENV') === 'production', // ← true na produkci, false lokálně
          ]);
     }

     /**
      * Načte data firmy z ARES podle IČ
      * Vrátí pole s klíči: company, address, dic
      * nebo null při chybě
      */
     public function lookup(string $ico): ?array
     {
          // IČ musí být 8 číslic (doplní se nulami zleva)
          $ico = str_pad(preg_replace('/\D/', '', $ico), 8, '0', STR_PAD_LEFT);

          if (strlen($ico) !== 8) {
               return null;
          }

          try {
               $response = $this->client->get(self::API_URL . $ico, [
                    'headers' => ['Accept' => 'application/json'],
               ]);

               $data = json_decode($response->getBody()->getContents(), true);

               if (empty($data)) {
                    return null;
               }

               return [
                    'company' => $this->extractCompany($data),
                    'address' => $this->extractAddress($data),
                    'dic'     => $data['dic'] ?? null,
               ];
          } catch (GuzzleException $e) {
               return null;
          }
     }

     private function extractCompany(array $data): string
     {
          return $data['obchodniJmeno']
               ?? $data['nazev']
               ?? '';
     }

     private function extractAddress(array $data): string
     {
          $addr = $data['sidlo'] ?? [];

          $parts = array_filter([
               ($addr['nazevUlice']    ?? '') . ' ' . ($addr['cisloDomovni'] ?? ''),
               $addr['nazevObce']      ?? '',
               $addr['psc']            ?? '',
          ]);

          // Fallback na textovou adresu pokud API vrátí jiný formát
          if (empty($parts) && isset($data['sidlo']['textovaAdresa'])) {
               return $data['sidlo']['textovaAdresa'];
          }

          return trim(implode(', ', $parts));
     }
}
