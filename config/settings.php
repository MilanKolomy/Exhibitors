<?php
declare(strict_types=1);

$env = __DIR__ . '/../.env';
if (file_exists($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Odeber inline komentář
        if (strpos($value, ' #') !== false) {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        // Místo putenv() použij $_ENV a $_SERVER
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

// Helper funkce — čte z $_ENV místo getenv()
function env(string $key, string $default = ''): string
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

function basePath(string $path = ''): string
{
    $base = env('APP_BASE_PATH') ?: '';
    $base = rtrim($base, '/');

    if ($base === '') {
        return '/' . ltrim($path, '/');
    }

    return $base . '/' . ltrim($path, '/');
}