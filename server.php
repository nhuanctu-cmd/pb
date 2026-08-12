<?php

if (PHP_SAPI !== 'cli-server') {
    require __DIR__ . '/public/index.php';
    exit;
}

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rawurldecode($requestUri);
$filePath = realpath(__DIR__ . '/public' . $path);

if ($filePath !== false && is_file($filePath) && str_starts_with($filePath, realpath(__DIR__ . '/public') . DIRECTORY_SEPARATOR)) {
    return false;
}

require __DIR__ . '/public/index.php';
