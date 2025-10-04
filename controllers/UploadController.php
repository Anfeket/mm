<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Auth.php';

class UploadController
{
	public static function form()
	{
		require_login();
		$title = "Upload - mm";
		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/upload/form.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	public static function store()
	{
		require_login();
		global $pdo;

		if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
			die("Upload failed");
		}

		$file = $_FILES['file'];
		$hash = md5_file($file['tmp_name']);
		$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$mime = mime_content_type($file['tmp_name']);
		$size = filesize($file['tmp_name']);

		[$width, $height] = [null, null];
		$duration = null;

		// Try to detect image size
		if (str_starts_with($mime, 'image/')) {
			[$width, $height] = getimagesize($file['tmp_name']);
		}

		// TODO: for videos, use ffmpeg to get $duration

		// build final storage path
		$dir1 = substr($hash, 0, 2);
		$dir2 = substr($hash, 2, 2);
		$uploadDir = __DIR__ . "/../public/uploads/$dir1/$dir2/";
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0775, true);
		}
		$finalPath = $uploadDir . $hash . "." . $ext;

		if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
			die("Failed to move uploaded file");
		}

		// save to DB
		$stmt = $pdo->prepare("
            INSERT INTO posts (author_id, post_type, mime_type, file_hash, file_ext,
                               original_file_name, file_size, width, height, duration, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

		$authorId = current_user()['id'];
		$postType = str_starts_with($mime, 'video/') ? 'video' : 'image';

		$stmt->execute([
			$authorId,
			$postType,
			$mime,
			$hash,
			$ext,
			$file['name'],
			$size,
			$width,
			$height,
			$duration,
			$_POST['description'] ?? null
		]);

		$postId = $pdo->lastInsertId();

		// --- TAG HANDLING ---
		$tagCategories = [
			'artist' => $_POST['artist'] ?? '',
			'copyright' => $_POST['copyrights'] ?? '',
			'general' => $_POST['tags'] ?? ''
		];

		foreach ($tagCategories as $category => $input) {
			$input = trim($input);
			if ($input === '') continue;

			$tags = preg_split('/\s+/', $input);

			foreach ($tags as $tagName) {
				$tagName = strtolower(trim($tagName));
				if ($tagName === '') continue;

				// 1. Check if tag exists (or alias)
				$stmt = $pdo->prepare("SELECT id, alias_tag_id FROM tags WHERE name = ?");
				$stmt->execute([$tagName]);
				$tag = $stmt->fetch(PDO::FETCH_ASSOC);

				if ($tag) {
					// Resolve alias if present
					$tagId = $tag['alias_tag_id'] ?: $tag['id'];
				} else {
					// 2. Create tag if it doesn’t exist, using its category
					$stmt = $pdo->prepare("
						INSERT INTO tags (name, category, post_count)
						VALUES (?, ?, 0)
					");
					$stmt->execute([$tagName, $category]);
					$tagId = $pdo->lastInsertId();
				}

				// 3. Insert into post_tags
				$stmt = $pdo->prepare("
					INSERT IGNORE INTO post_tags (post_id, tag_id, added_by)
					VALUES (?, ?, ?)
				");
				$stmt->execute([$postId, $tagId, $authorId]);

				// 4. Increment tag usage count
				$stmt = $pdo->prepare("UPDATE tags SET post_count = post_count + 1 WHERE id = ?");
				$stmt->execute([$tagId]);
			}
		}

		header("Location: /post/$postId");
		exit;
	}
}
