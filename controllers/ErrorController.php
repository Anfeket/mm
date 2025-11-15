<?php

class ErrorController {
	public static function notFound() {
		http_response_code(404);
        $title = "Page not found - mm";
        include __DIR__ . '/../views/layout/head.php';
        include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/404.php';
        include __DIR__ . '/../views/layout/footer.php';
	}

	public static function forbidden() {
		http_response_code(403);
		$title = "Forbidden - mm";
		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/403.php';
		include __DIR__ . '/../views/layout/footer.php';
	}
}
