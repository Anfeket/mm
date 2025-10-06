<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Post.php'; // or wherever your Post model is

global $pdo;

// select posts that don’t have a thumbnail
$stmt = $pdo->query("
    SELECT id, file_hash, file_ext, mime_type
    FROM posts
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$processed = 0;
foreach ($posts as $post) {
    $dir1 = substr($post['file_hash'], 0, 2);
    $dir2 = substr($post['file_hash'], 2, 2);
    $uploadPath = __DIR__ . "/../public/uploads/$dir1/$dir2/{$post['file_hash']}.{$post['file_ext']}";
    $thumbPath = __DIR__ . "/../public/thumbs/$dir1/$dir2/{$post['file_hash']}.webp";

    if (!file_exists($uploadPath)) {
        echo "Skipping missing file: {$uploadPath}\n";
        continue;
    }

    if (file_exists($thumbPath)) {
        continue; // already has thumbnail
    }

    echo "Generating thumbnail for post #{$post['id']}...\n";
    Post::generateThumbnail($uploadPath, $post['mime_type'], $post['file_hash']);
    $processed++;
}

echo "Done. Generated $processed thumbnails.\n";
