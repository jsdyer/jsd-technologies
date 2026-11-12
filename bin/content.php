<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
if (PHP_SAPI !== 'cli') exit(1);
$command = $argv[1] ?? '';
if (!in_array($command, ['import', 'export'], true)) { fwrite(STDERR, "Usage: php bin/content.php import|export\n"); exit(1); }
$db = database();
$db->exec('CREATE TABLE IF NOT EXISTS site_content (site_key VARCHAR(32) PRIMARY KEY, payload TEXT NOT NULL)');
if ($command === 'import') {
    $db->beginTransaction();
    try {
        foreach (glob(dirname(__DIR__) . '/content/*.json') as $file) {
            $json = file_get_contents($file); json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $key = basename($file, '.json');
            $db->prepare('DELETE FROM site_content WHERE site_key = ?')->execute([$key]);
            $db->prepare('INSERT INTO site_content (site_key, payload) VALUES (?, ?)')->execute([$key, $json]);
        }
        $db->commit(); echo "Content imported.\n";
    } catch (Throwable $error) { $db->rollBack(); throw $error; }
} else {
    foreach ($db->query('SELECT site_key, payload FROM site_content') as $row) {
        if (!preg_match('/^[a-z]+$/', $row['site_key'])) throw new RuntimeException('Invalid site key');
        file_put_contents(dirname(__DIR__) . '/content/' . $row['site_key'] . '.json', json_encode(json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    }
    echo "Content exported to versioned JSON.\n";
}
