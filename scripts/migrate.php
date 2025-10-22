<?php
require __DIR__ . '/../config/config.php';

$applied = $pdo->query("SELECT name FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/../migrations/*.{sql,php}', GLOB_BRACE);
sort($files);

foreach ($files as $file) {
	$name = basename($file);
	if (in_array($name, $applied)) continue;

	echo "Applying $name...\n";

	if (str_ends_with($file, '.sql')) {
		$sql = file_get_contents($file);
		$pdo->exec($sql);
	} elseif (str_ends_with($file, '.php')) {
		include $file;
	}

	$pdo->prepare("INSERT INTO migrations (name) VALUES (?)")->execute([$name]);
	echo "→ Done\n";
}
