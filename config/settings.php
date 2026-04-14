<?php

declare(strict_types=1);

$env = __DIR__ . '/../.env';
if (file_exists($env)) {
     foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
          $line = trim($line);

          if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;

          [$key, $value] = explode('=', $line, 2);

          // Odeber inline komentář (# a vše za ním)
          if (strpos($value, ' #') !== false) {
               $value = trim(explode(' #', $value, 2)[0]);
          }

          putenv(trim($key) . '=' . trim($value));
     }
}

function basePath(string $path = ''): string
{
     $base = rtrim(getenv('APP_BASE_PATH') ?: '', '/');
     return $base . '/' . ltrim($path, '/');
}
