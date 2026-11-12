<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) !== '/' && parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) !== '/index.php') {
    http_response_code(404); echo render_page(site_key(), '__missing__'); exit;
}
try {
    if (isset($_GET['feed']) && $_GET['feed'] === 'rss') {
        header('Content-Type: application/rss+xml; charset=utf-8'); echo render_feed(content(site_key()), site_key());
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo render_page(site_key(), isset($_GET['note']) && is_string($_GET['note']) ? $_GET['note'] : null);
    }
} catch (Throwable $error) {
    error_log($error->getMessage()); http_response_code(503); echo 'The site is temporarily unavailable. Please try again shortly.';
}
