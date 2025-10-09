<?php
class Tag
{
	public static function forPost($postId)
	{
		global $pdo;
		$stmt = $pdo->prepare("
            SELECT t.id, t.name, t.category
            FROM tags t
            INNER JOIN post_tags pt ON pt.tag_id = t.id
            WHERE pt.post_id = ?
            ORDER BY t.name ASC
        ");
		$stmt->execute([$postId]);
		$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// organize by category
		$grouped = [
			'artist' => [],
			'copyright' => [],
			'general' => [],
			'meta' => []
		];
		foreach ($tags as $tag) {
			$cat = strtolower($tag['category']);
			if (isset($grouped[$cat])) {
				$grouped[$cat][] = $tag['name'];
			}
		}
		return $grouped;
	}

	public static function search($query, $category = null, $limit = 10)
	{
		global $pdo;

		if ($category) {
			$stmt = $pdo->prepare("
				SELECT name, category, post_count
				FROM tags
				WHERE category = :category AND name LIKE CONCAT('%', :query, '%')
				ORDER BY post_count DESC
				LIMIT :limit
			");
			$stmt->bindValue(':category', $category, PDO::PARAM_STR);
			$stmt->bindValue(':query', $query, PDO::PARAM_STR);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
			$stmt->execute();
		} else {
			$stmt = $pdo->prepare("
				SELECT name, category, post_count
				FROM tags
				WHERE name LIKE CONCAT('%', :query, '%')
				ORDER BY post_count DESC
				LIMIT :limit
			");
			$stmt->bindValue(':query', $query, PDO::PARAM_STR);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
			$stmt->execute();
		}

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
