<?php
declare(strict_types=1);
ob_start();
require dirname(__DIR__) . '/app/core.php';
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
check(escape('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML escaping');
foreach (glob(dirname(__DIR__) . '/content/*.json') as $file) {
 $key = basename($file, '.json'); putenv('SITE_KEY=' . $key);
 $data = content($key); $html = render_page($key);
 check(str_contains($html, '<h1>'), "$key renders with a main heading");
 check(str_contains($html, 'rel="canonical"'), "$key has canonical metadata");
 foreach ($data['notes'] ?? [] as $note) {
  $page = render_page($key, $note['slug']);
  check(str_contains($page, escape($note['title'])), 'Article ' . $note['slug']);
 }
 http_response_code(200); $missing = render_page($key, '../../etc/passwd');
 check(http_response_code() === 404 && str_contains($missing, '404 /'), "$key returns a safe 404 for unknown article");
 check(!str_contains($missing, 'root:x:'), 'No path traversal');
}
$dbFile = tempnam(sys_get_temp_dir(), 'jsd-test-'); putenv('DB_DSN=sqlite:' . $dbFile);
$db = database(); $db->exec('CREATE TABLE site_content (site_key VARCHAR(32) PRIMARY KEY, payload TEXT NOT NULL)');
$lastKey = basename($file, '.json');
$statement = $db->prepare('INSERT INTO site_content VALUES (?, ?)');
$statement->execute([$lastKey, file_get_contents($file)]);
putenv('CONTENT_STORE=database');
check(content($lastKey) === $data, 'SQLite content matches the file source');
$db = null; unlink($dbFile); putenv('CONTENT_STORE'); putenv('DB_DSN');
echo "All checks passed.\n";
putenv('SITE_KEY=workshop'); $workshop = content('workshop');
foreach ($workshop['guides'] as $guide) {
 check(preg_match('/^[a-z0-9-]+$/D', $guide['slug']) === 1, 'Safe guide slug');
 http_response_code(200); $page = render_page('workshop', $guide['slug'], 'guide');
 check(http_response_code() === 200 && str_contains($page, escape($guide['title'])), 'Guide renders: '.$guide['slug']);
 check(str_contains($page, 'https://jsd.technology/how-to/'.$guide['slug'].'/'), 'Guide canonical URL');
 check(str_contains($page, 'data-copy-code'), 'Guide has copyable example');
 foreach ($guide['related'] as $related) check(in_array($related, array_column($workshop['guides'], 'slug'), true), 'Related guide exists');
}
foreach ($workshop['tips'] as $tip) check(in_array($tip['guide'], array_column($workshop['guides'], 'slug'), true), 'Tip target exists');
check(count(array_unique(array_column($workshop['guides'], 'slug'))) === count($workshop['guides']), 'Unique guide slugs');
foreach (['guides','tips','community'] as $view) check(str_contains(render_page('workshop', null, $view), '<h1>'), "$view page renders");
check(route_request('workshop', '/how-to/run-a-php-site-locally/') === ['guide','run-a-php-site-locally'], 'Clean guide route');
check(route_request('studio', '/how-to/run-a-php-site-locally/') === ['missing',null], 'Workshop routes do not leak onto studio');
check(route_request('workshop', '/how-to/../../etc/passwd/') === ['missing',null], 'Traversal route rejected');
check(route_request('workshop', '/', ['note'=>['bad']]) === ['missing',null], 'Array query rejected safely');
http_response_code(200); $missing = render_page('workshop', 'missing-guide', 'guide');
check(http_response_code() === 404 && str_contains($missing, 'noindex'), 'Missing guide is a non-indexable 404');
check(substr_count(render_feed($workshop, 'workshop'), '<item>') === count($workshop['guides']) + count($workshop['notes']), 'RSS contains notes and guides');
check(str_contains(render_sitemap('workshop'), 'https://jsd.technology/how-to/'), 'Sitemap contains guide URLs');
echo "Workshop checks passed.\n";
