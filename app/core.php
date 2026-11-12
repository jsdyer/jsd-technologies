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
function render_feed(array $data, string $key): string {
    $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . escape($data['title']) . '</title><link>' . escape(page_url($key)) . '</link><description>' . escape($data['description']) . '</description>';
    foreach ($data['notes'] ?? [] as $note) {
        $url = ['workshop'=>'https://jsd.technology/', 'personal'=>'https://jeffreydyer.ca/'][$key] . '?note=' . rawurlencode($note['slug']);
        $xml .= '<item><title>' . escape($note['title']) . '</title><link>' . escape($url) . '</link><guid>' . escape($url) . '</guid><pubDate>' . date(DATE_RSS, strtotime($note['date'] . ' 12:00:00 UTC')) . '</pubDate><description>' . escape($note['summary']) . '</description></item>';
    }
    return $xml . '</channel></rss>';
}
function render_page(string $key, ?string $slug = null): string {
    $data = content($key); $note = null;
    if ($slug !== null) {
        foreach ($data['notes'] ?? [] as $item) if ($item['slug'] === $slug) $note = $item;
        if (!$note) { http_response_code(404); $notFound = true; }
    }
    $title = $note ? $note['title'] . ' — ' . $data['title'] : $data['title'];
    ob_start(); require __DIR__ . '/layout.php'; return ob_get_clean();
}
