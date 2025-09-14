<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/../models/User.php';

class PostController {
    public static function recent() {
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
			require_once '../controllers/ErrorController.php';
			$controller = new ErrorController();
			$controller->notFound();
			return;
		}

		// fetch tags
		$tags = Tag::forPost($id);

		$title = "Post #" . $post['id'] . " - mm";
		$uploader = User::findById($post['author_id']);

		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/post/post.php';
		include __DIR__ . '/../views/layout/footer.php';
	}
}
