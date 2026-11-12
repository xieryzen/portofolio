<?php

declare(strict_types=1);

/**
 * Router untuk PHP built-in server:
 *   php -S 0.0.0.0:8080 -t public router.php
 *
 * File statis di public/ dilayani langsung, sisanya masuk ke front controller.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicDirectory = __DIR__ . '/public';
$file = realpath($publicDirectory . $uri);

if (
    $uri !== '/'
    && $file !== false
    && str_starts_with($file, realpath($publicDirectory) . DIRECTORY_SEPARATOR)
    && is_file($file)
) {
    return false;
}

require __DIR__ . '/public/index.php';
