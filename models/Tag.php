<?php
class Tag {
    public static function forPost($postId) {
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
            $cat = strtolower($tag['category']); // DB enum might be 'Artist'
            if (isset($grouped[$cat])) {
                $grouped[$cat][] = $tag['name'];
            }
        }
        return $grouped;
    }
}
