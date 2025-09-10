<?php
require_once '../config/config.php';

// Parse request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Simple routing
switch ($segments[0]) {
    case '':
        require_once '../controllers/PostController.php';
        $controller = new PostController();
        $controller->index();
        break;
    case 'post':
        require_once '../controllers/PostController.php';
        $controller = new PostController();
        $controller->show($segments[1]);
        break;
    case 'upload':
        require_once '../controllers/UploadController.php';
        $controller = new UploadController();
        $controller->form();
        break;
    case 'api':
        if ($segments[1] === 'upload') {
            require_once '../controllers/UploadController.php';
            $controller = new UploadController();
            $controller->process();
        }
        break;
	default:
        require_once '../controllers/ErrorController.php';
		$controller = new ErrorController();
		$controller->notFound();
		break;
}
