<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../views/layout/header.php';
require_once __DIR__ . '/../views/layout/sidebar.php';

class PostsController {
    public static function show($id) {

        include __DIR__ . '/../views/layout/head.php';
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/post/post.php';
        include __DIR__ . '/../views/layout/footer.php';
    }
}
