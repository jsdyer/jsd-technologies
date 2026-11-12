<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$key = $argv[1] ?? 'studio';
if (!in_array($key, ['studio','workshop','personal'], true)) exit(1);
define('STATIC_EXPORT', true); define('SITE_KEY', $key);
require dirname(__DIR__) . '/app/core.php';
$out = rtrim($argv[2] ?? dirname(__DIR__) . '/dist', '/');
if (!is_dir($out . '/assets')) mkdir($out . '/assets', 0755, true);
foreach (glob(dirname(__DIR__) . '/public/assets/*') as $asset) copy($asset, $out . '/assets/' . basename($asset));
file_put_contents($out . '/index.html', render_page($key));
foreach (content($key)['notes'] ?? [] as $note) {
    if (!preg_match('/^[a-z0-9-]+$/', $note['slug'])) throw new RuntimeException('Invalid slug');
    $dir = $out . '/notes/' . $note['slug']; if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/index.html', render_page($key, $note['slug']));
}
if ($key === 'workshop') {
    foreach (['how-to'=>'guides', 'tips'=>'tips', 'community'=>'community'] as $path=>$view) {
        $dir = $out . '/' . $path; if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($dir . '/index.html', render_page($key, null, $view));
    }
    foreach (content($key)['guides'] as $guide) {
        if (!preg_match('/^[a-z0-9-]+$/D', $guide['slug'])) throw new RuntimeException('Invalid guide slug');
        $dir = $out . '/how-to/' . $guide['slug']; if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($dir . '/index.html', render_page($key, $guide['slug'], 'guide'));
    }
}
file_put_contents($out . '/sitemap.xml', render_sitemap($key));
file_put_contents($out . '/robots.txt', "User-agent: *\nDisallow: /\n");
file_put_contents($out . '/feed.xml' , render_feed(content($key), $key));
echo "Exported $key to $out\n";
