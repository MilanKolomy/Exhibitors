<?php
declare(strict_types=1);

function __t(string $key, array $replace = [], ?string $locale = null): string
{
    // Bez statické cache — locale se může měnit per request
    $locale = $locale ?? ($_SESSION['locale'] ?? 'cs');

    $parts = explode('.', $key);
    $file  = array_shift($parts);

    $path     = __DIR__ . "/../lang/{$locale}/{$file}.php";
    $fallback = __DIR__ . "/../lang/cs/{$file}.php";

    $translations = file_exists($path)
        ? require $path
        : (file_exists($fallback) ? require $fallback : []);

    $value = $translations;
    foreach ($parts as $part) {
        $value = is_array($value) ? ($value[$part] ?? $key) : $key;
    }

    if (is_array($value)) return $key;

    foreach ($replace as $k => $v) {
        $value = str_replace(':' . $k, $v, $value);
    }

    return $value;
}