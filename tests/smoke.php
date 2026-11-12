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
