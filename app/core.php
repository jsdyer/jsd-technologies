<?php
declare(strict_types=1);

function escape(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_key(): string {
    if (defined('SITE_KEY')) return SITE_KEY;
    $fixed = getenv('SITE_KEY');
    if ($fixed !== false && in_array($fixed, ['studio', 'workshop', 'personal'], true)) return $fixed;
    $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
    return in_array($host, ['jsd.technology', 'www.jsd.technology'], true) ? 'workshop' : 'studio';
}
function database(): PDO {
    $dsn = getenv('DB_DSN') ?: 'sqlite:' . dirname(__DIR__) . '/var/content.sqlite';
    $db = new PDO($dsn, getenv('DB_USER') ?: null, getenv('DB_PASSWORD') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (str_starts_with($dsn, 'sqlite:')) $db->exec('PRAGMA busy_timeout = 5000');
    return $db;
}
function content(string $key): array {
    if (getenv('CONTENT_STORE') === 'database') {
        $stmt = database()->prepare('SELECT payload FROM site_content WHERE site_key = :key');
        $stmt->execute(['key' => $key]);
        $payload = $stmt->fetchColumn();
        if ($payload === false) throw new RuntimeException('Content is not imported. Run php bin/content.php import.');
        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }
    return json_decode(file_get_contents(dirname(__DIR__) . '/content/' . $key . '.json'), true, 512, JSON_THROW_ON_ERROR);
}
function page_url(string $key, string $path = ''): string {
    if (defined('STATIC_EXPORT')) {
        $roots = ['studio' => '/', 'workshop' => '/workshop/', 'personal' => '/personal/'];
        return $roots[$key] . ltrim($path, '/');
    }
    $roots = ['studio' => 'https://jsdtechnologies.ca/', 'workshop' => 'https://jsd.technology/', 'personal' => 'https://jeffreydyer.ca/'];
    return $roots[$key] . ltrim($path, '/');
}
function asset_url(string $file): string { return (defined('STATIC_EXPORT') ? page_url(site_key()) : '/') . 'assets/' . $file; }
function note_url(string $key, string $slug): string { return defined('STATIC_EXPORT') ? page_url($key, 'notes/' . rawurlencode($slug) . '/') : '/?note=' . rawurlencode($slug); }
function feed_url(string $key): string { return defined('STATIC_EXPORT') ? page_url($key, 'feed.xml') : '/?feed=rss'; }
function production_url(string $key, string $path = ''): string {
    return ['studio'=>'https://jsdtechnologies.ca/', 'workshop'=>'https://jsd.technology/', 'personal'=>'https://jeffreydyer.ca/'][$key] . ltrim($path, '/');
}
function workshop_url(string $path = ''): string { return defined('STATIC_EXPORT') ? page_url('workshop', $path) : '/' . ltrim($path, '/'); }
function guide_url(string $slug): string { return workshop_url('how-to/' . rawurlencode($slug) . '/'); }
function contribution_url(string $kind, string $title = ''): string {
    $templates = ['question'=>'how-to-request.yml', 'tip'=>'share-a-tip.yml', 'correction'=>'correction.yml'];
    return 'https://github.com/jsdyer/jsd-technologies/issues/new?' . http_build_query(array_filter(['template'=>$templates[$kind], 'title'=>$title], fn($value) => $value !== ''), '', '&', PHP_QUERY_RFC3986);
}
function route_request(string $key, string $path, array $query = []): array {
    if ($path === '/' || $path === '/index.php') {
        if (($query['feed'] ?? null) === 'rss') return ['feed', null];
        if (isset($query['note'])) return is_string($query['note']) ? ['note', $query['note']] : ['missing', null];
        return ['home', null];
    }
    if ($path === '/sitemap.xml') return ['sitemap', null];
    if ($path === '/robots.txt') return ['robots', null];
    if ($key === 'workshop') {
        if ($path === '/feed.xml') return ['feed', null];
        foreach (['how-to'=>'guides', 'tips'=>'tips', 'community'=>'community'] as $segment=>$view) {
            if ($path === '/' . $segment . '/') return [$view, null];
        }
        if (preg_match('~^/how-to/([a-z0-9-]+)/$~D', $path, $matches)) return ['guide', $matches[1]];
    }
    return ['missing', null];
}
function render_sitemap(string $key): string {
    $data = content($key); $paths = [''];
    foreach ($data['notes'] ?? [] as $note) $paths[] = '?note=' . rawurlencode($note['slug']);
    if ($key === 'workshop') {
        array_push($paths, 'how-to/', 'tips/', 'community/');
        foreach ($data['guides'] as $guide) $paths[] = 'how-to/' . $guide['slug'] . '/';
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($paths as $path) $xml .= '<url><loc>' . escape(production_url($key, $path)) . '</loc></url>';
    return $xml . '</urlset>';
}
function render_feed(array $data, string $key): string {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . escape($data['title']) . '</title><link>' . escape(production_url($key)) . '</link><description>' . escape($data['description']) . '</description>';
    $items = [];
    foreach ($data['notes'] ?? [] as $note) { $note['path'] = '?note=' . rawurlencode($note['slug']); $items[] = $note; }
    foreach ($data['guides'] ?? [] as $guide) { $guide['path'] = 'how-to/' . $guide['slug'] . '/'; $items[] = $guide; }
    usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
    foreach ($items as $item) {
        $url = production_url($key, $item['path']);
        $xml .= '<item><title>' . escape($item['title']) . '</title><link>' . escape($url) . '</link><guid>' . escape($url) . '</guid><pubDate>' . date(DATE_RSS, strtotime($item['date'] . ' 12:00:00 UTC')) . '</pubDate><description>' . escape($item['summary']) . '</description></item>';
    }
    return $xml . '</channel></rss>';
}
function render_page(string $key, ?string $slug = null, string $view = 'home'): string {
    $data = content($key); $note = null; $guide = null;
    if ($slug !== null && $view === 'home') $view = 'note';
    if ($view === 'note') {
        foreach ($data['notes'] ?? [] as $item) if ($item['slug'] === $slug) $note = $item;
        if (!$note) $view = 'missing';
    }
    if ($view === 'guide') {
        foreach ($data['guides'] ?? [] as $item) if ($item['slug'] === $slug) $guide = $item;
        if (!$guide || $key !== 'workshop') $view = 'missing';
    }
    if (in_array($view, ['guides','tips','community'], true) && $key !== 'workshop') $view = 'missing';
    if (!in_array($view, ['home','note','guide','guides','tips','community'], true)) { http_response_code(404); $notFound = true; }
    $pageTitles = ['guides'=>'Practical how-tos', 'tips'=>'Quick tips', 'community'=>'The shared workbench'];
    $pageDescriptions = ['guides'=>'Practical Linux, PHP, SQLite, DNS and Apache how-tos with commands, checks and references.', 'tips'=>'Small, useful tips for Linux servers, PHP websites, local DNS and digital archives.', 'community'=>'Ask for a how-to, share a technical tip or help improve a guide. An open invitation to learn together.'];
    $title = ($guide['title'] ?? $note['title'] ?? $pageTitles[$view] ?? '') ?: $data['title'];
    if ($title !== $data['title']) $title .= ' — ' . $data['title'];
    $description = $guide['summary'] ?? $note['summary'] ?? $pageDescriptions[$view] ?? $data['description'];
    $canonicalPath = $guide ? 'how-to/' . $guide['slug'] . '/' : ($note ? '?note=' . rawurlencode($note['slug']) : (['guides'=>'how-to/', 'tips'=>'tips/', 'community'=>'community/'][$view] ?? ''));
    ob_start(); require __DIR__ . '/layout.php'; return ob_get_clean();
}
