<?php
require __DIR__ . '/../config/config.php';
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$applied = $pdo->query("SELECT name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/../migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied)) continue;
    echo "Applying $name...\n";
    $sql = file_get_contents($file);
    $pdo->exec($sql);
    $pdo->prepare("INSERT INTO migrations (name) VALUES (?)")->execute([$name]);
    echo "→ Done\n";
}
