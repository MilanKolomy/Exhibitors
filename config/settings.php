<?php
declare(strict_types=1);

$env = __DIR__ . '/../.env';
if (file_exists($env)) {
    foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}