<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
try {
    $key = site_key();
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    // Canonical trailing slash for known workshop paths only.
    if ($key === 'workshop' && preg_match('~^/(how-to(?:/[a-z0-9-]+)?|tips|community)$~D', $path)) {
        header('Location: ' . $path . '/', true, 301); exit;
    }
    [$view, $slug] = route_request($key, $path, $_GET);
    if ($view === 'feed') {
        header('Content-Type: application/rss+xml; charset=utf-8'); echo render_feed(content($key), $key);
    } elseif ($view === 'sitemap') {
        header('Content-Type: application/xml; charset=utf-8'); echo render_sitemap($key);
    } elseif ($view === 'robots') {
        header('Content-Type: text/plain; charset=utf-8'); echo "User-agent: *\nAllow: /\nSitemap: " . production_url($key, 'sitemap.xml') . "\n";
    } else {
        header('Content-Type: text/html; charset=utf-8'); echo render_page($key, $slug, $view);
    }
} catch (Throwable $error) {
    error_log($error->getMessage()); http_response_code(503); echo 'The site is temporarily unavailable. Please try again shortly.';
}
