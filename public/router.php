<?php
// Router for the local PHP development server. Apache uses .htaccess instead.
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$asset = realpath(__DIR__ . rawurldecode($path));
if (str_starts_with($path, '/assets/') && $asset !== false && str_starts_with($asset, __DIR__ . '/assets/') && is_file($asset)) return false;
require __DIR__ . '/index.php';
