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
            SELECT id, width, height, score,
                   file_hash, file_ext
            FROM posts
            ORDER BY created_at DESC
            LIMIT ?
        ");
		$stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
		$stmt->execute();
		$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($posts as &$post) {
			$post['file_path'] = self::filePath($post);
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
}
