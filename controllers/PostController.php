<?php
require_once __DIR__ . '/../models/Post.php';

class PostController {
    public static function index() {
        $posts = Post::recent(20);
        $title = "Posts - mm";

        include __DIR__ . '/../views/layout/head.php';
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/post/index.php';
        include __DIR__ . '/../views/layout/footer.php';
    }

    public static function show($id) {
        $post = Post::find($id);
        if (!$post) {
            http_response_code(404);
            echo "Post not found";
            return;
        }
        $title = "Post #" . $post['id'] . " - mm";

        include __DIR__ . '/../views/layout/head.php';
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/post/post.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
