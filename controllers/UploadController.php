<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/Discord.php';

class UploadController
{
	public static function form()
	{
		Auth::require_login();
		$title = "Upload - mm";
		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/upload/form.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	public static function store()
	{
		Auth::require_login();
		global $pdo;

		$file = $_FILES['file'] ?? null;
		if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
			die("Upload failed");
		}

		$authorId = Auth::current_user()['id'];

		// 1) analyze + move file
		$meta = self::analyzeFile($file['tmp_name']);
		$paths = self::storeFile($file, $meta['hash']); // returns ['final','ext']

		// 2) DB: save post + tags in a transaction
		try {
			$pdo->beginTransaction();

			$postId = self::savePost($pdo, $authorId, $file, $meta, $paths);

			// handle tags (reads $_POST['artist'], 'copyrights', 'tags')
			self::handleTags($pdo, $postId, $authorId);

			$pdo->commit();
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			// optional: remove file if DB failed
			if (file_exists($paths['final'])) @unlink($paths['final']);
			throw $e; // or die("DB error");
		}

		// 3) generate thumbnail after DB commit (so upload isn't held by DB)
		Post::generateThumbnail($paths['final'], $meta['mime'], $meta['hash']);

		// Send webhoook
		$post = Post::find($postId);
		Discord::postUpload($post);

		header("Location: /post/$postId");
		exit;
	}

	/* -------------------- helpers -------------------- */

	private static function analyzeFile($tmpPath)
	{
		$hash = md5_file($tmpPath);
		$mime = mime_content_type($tmpPath);
		$size = filesize($tmpPath);

		[$width, $height] = [null, null];
		$duration = null;

		if (str_starts_with($mime, 'image/')) {
			[$width, $height] = getimagesize($tmpPath);
		} elseif (str_starts_with($mime, 'video/')) {
			// optional: use ffprobe to get duration
			// $out = shell_exec("ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg($tmpPath));
			// $duration = $out ? (float) $out : null;
		}

		return compact('hash', 'mime', 'size', 'width', 'height', 'duration');
	}

	private static function storeFile($file, $hash)
	{
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$dir1 = substr($hash, 0, 2);
		$dir2 = substr($hash, 2, 2);
		$uploadDir = __DIR__ . "/../public/uploads/$dir1/$dir2/";
		if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

		$finalPath = $uploadDir . $hash . "." . $ext;
		if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
			die("Failed to move uploaded file");
		}

		return ['final' => $finalPath, 'ext' => $ext];
	}

	private static function savePost($pdo, $authorId, $file, $meta, $paths)
	{
		$postType = str_starts_with($meta['mime'], 'video/') ? 'video' : 'image';

		$stmt = $pdo->prepare("
        INSERT INTO posts (author_id, post_type, mime_type, file_hash, file_ext,
                           original_file_name, file_size, width, height, duration, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

		$stmt->execute([
			$authorId,
			$postType,
			$meta['mime'],
			$meta['hash'],
			$paths['ext'],
			$file['name'],
			$meta['size'],
			$meta['width'],
			$meta['height'],
			$meta['duration'],
			$_POST['description'] ?? null
		]);

		return $pdo->lastInsertId();
	}

	private static function handleTags($pdo, $postId, $authorId)
	{
		// categories and corresponding input names
		$tagCategories = [
			'artist'   => $_POST['artist'] ?? '',
			'copyright' => $_POST['copyright'] ?? '',
			'general'  => $_POST['general'] ?? ''
		];

		// 1) parse & normalize all requested tags, grouped by category
		$tagsByCategory = [];
		$allNames = []; // unique list
		foreach ($tagCategories as $category => $input) {
			$input = trim((string)$input);
			if ($input === '') continue;

			$parts = preg_split('/\s+/', $input, -1, PREG_SPLIT_NO_EMPTY);
			$normalized = [];
			foreach ($parts as $raw) {
				$name = self::normalizeTagName($raw);
				if ($name === '') continue;
				$normalized[] = $name;
				$allNames[$name] = true;
			}
			if (!empty($normalized)) $tagsByCategory[$category] = $normalized;
		}

		if (empty($allNames)) return; // nothing to do

		$allNames = array_keys($allNames);

		// 2) fetch existing tags in one query
		$placeholders = implode(',', array_fill(0, count($allNames), '?'));
		$stmt = $pdo->prepare("SELECT id, name, alias_tag_id FROM tags WHERE name IN ($placeholders)");
		$stmt->execute($allNames);
		$existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$nameToId = [];
		foreach ($existing as $row) {
			// use alias target if set
			$canonicalId = $row['alias_tag_id'] ? intval($row['alias_tag_id']) : intval($row['id']);
			$nameToId[$row['name']] = $canonicalId;
		}

		// 3) create missing tags
		$insertStmt = $pdo->prepare("INSERT INTO tags (name, category, post_count) VALUES (?, ?, 0)");
		foreach ($allNames as $name) {
			if (isset($nameToId[$name])) continue; // exists already

			// find which category to give this new tag (pick first category that requested it)
			$giveCategory = 'general';
			foreach ($tagsByCategory as $cat => $names) {
				if (in_array($name, $names, true)) {
					$giveCategory = $cat;
					break;
				}
			}

			$insertStmt->execute([$name, $giveCategory]);
			$nameToId[$name] = $pdo->lastInsertId();
		}

		// 4) insert post_tags and bump post_count
		$linkStmt = $pdo->prepare("
        INSERT IGNORE INTO post_tags (post_id, tag_id, added_by)
        VALUES (?, ?, ?)
    ");
		$countStmt = $pdo->prepare("UPDATE tags SET post_count = post_count + 1 WHERE id = ?");

		// Avoid duplicate tag insertion per post: build a set
		$seenTagIds = [];

		foreach ($tagsByCategory as $category => $names) {
			foreach ($names as $name) {
				$tagId = $nameToId[$name] ?? null;
				if (!$tagId) continue;
				if (isset($seenTagIds[$tagId])) continue;
				$seenTagIds[$tagId] = true;

				$linkStmt->execute([$postId, $tagId, $authorId]);
				$countStmt->execute([$tagId]);
			}
		}
	}

	private static function normalizeTagName($raw)
	{
		// Basic normalization: trim, lowercase, collapse whitespace to underscore
		$n = trim($raw);
		$n = mb_strtolower($n, 'UTF-8');
		$n = preg_replace('/\s+/', '_', $n);    // turn spaces into underscores
		$n = preg_replace('/[^a-z0-9_\-]/u', '', $n); // allow a-z,0-9,_,- (tune as needed)
		return $n;
	}
}
