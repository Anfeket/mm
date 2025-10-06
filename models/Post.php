<?php
class Post
{
	public static function find($id)
	{
		global $pdo;
		$stmt = $pdo->prepare("
            SELECT id, author_id, post_type, file_size, width, height, favorites_count, score,
                   description, source, file_hash, file_ext, created_at
            FROM posts
            WHERE id = ?
        ");
		$stmt->execute([$id]);
		$post = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($post) {
			$post['file_path'] = self::filePath($post);
			$post['file_size_human'] = self::formatSize($post['file_size']);
		}

		return $post ?: null;
	}

	public static function recent($limit = 20)
	{
		global $pdo;
		$stmt = $pdo->prepare("
            SELECT id, width, height, score, file_hash, file_ext, post_type
            FROM posts
            ORDER BY created_at DESC
            LIMIT ?
        ");
		$stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
		$stmt->execute();
		$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($posts as &$post) {
			$post['file_path'] = self::filePath($post);
			$post['thumb_path'] = self::thumbPath($post);
		}

		return $posts;
	}

	private static function filePath($post)
	{
		$hash = $post['file_hash']; // e.g. "a1b2c3d4e5..."
		$ext  = $post['file_ext'];  // e.g. "jpg"

		// break into subfolders: a1/b2/
		$dir1 = substr($hash, 0, 2);
		$dir2 = substr($hash, 2, 2);

		return "/uploads/$dir1/$dir2/" . $hash . "." . $ext;
	}

	private static function thumbPath($post)
	{
		$hash = $post['file_hash']; // e.g. "a1b2c3d4e5..."

		// break into subfolders: a1/b2/
		$dir1 = substr($hash, 0, 2);
		$dir2 = substr($hash, 2, 2);

		return "/thumbs/$dir1/$dir2/" . $hash . ".webp";
	}

	private static function formatSize($bytes)
	{
		if ($bytes < 1024) {
			return $bytes . ' B';
		} elseif ($bytes < 1048576) {
			return round($bytes / 1024, 1) . ' KB';
		} elseif ($bytes < 1073741824) {
			return round($bytes / 1048576, 1) . ' MB';
		} else {
			return round($bytes / 1073741824, 1) . ' GB';
		}
	}

	public static function generateThumbnail($finalPath, $mime, $hash)
	{
		$dir1 = substr($hash, 0, 2);
		$dir2 = substr($hash, 2, 2);
		$thumbDir = __DIR__ . "/../public/thumbs/$dir1/$dir2/";
		if (!is_dir($thumbDir)) mkdir($thumbDir, 0775, true);

		$thumbPath = $thumbDir . $hash . ".webp";

		if (file_exists($thumbPath)) return; // already exists

		if (str_starts_with($mime, 'image/')) {
			$src = imagecreatefromstring(file_get_contents($finalPath));
			if (!$src) return;
			$w = imagesx($src);
			$h = imagesy($src);
			$max = 300;
			if ($w >= $h) {
				$newW = $max;
				$newH = intval($h * ($max / $w));
			} else {
				$newH = $max;
				$newW = intval($w * ($max / $h));
			}
			$thumb = imagecreatetruecolor($newW, $newH);
			imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
			imagewebp($thumb, $thumbPath, 80);
			imagedestroy($src);
			imagedestroy($thumb);
		} elseif (str_starts_with($mime, 'video/')) {
			$cmd = sprintf(
				'ffmpeg -y -ss 00:00:01 -i %s -vframes 1 -vf "scale=\'min(300,iw)\':-1" %s 2>&1',
				escapeshellarg($finalPath),
				escapeshellarg($thumbPath)
			);
			shell_exec($cmd);
		}
	}
}
