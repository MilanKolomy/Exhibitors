<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AresService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AresController
{
     private AresService $aresService;

     public function __construct(AresService $aresService)
     {
          $this->aresService = $aresService;
     }

     public function lookup(Request $request, Response $response, array $args): Response
     {
          $ico  = trim($args['ico'] ?? '');
          $data = $this->aresService->lookup($ico);

          if ($data) {
               $payload = array_merge(['success' => true], $data);
          } else {
               $payload = ['success' => false, 'message' => 'Subjekt nebyl nalezen'];
          }

          $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

          return $response
               ->withHeader('Content-Type', 'application/json')
               ->withHeader('Cache-Control', 'no-store');
     }
}
