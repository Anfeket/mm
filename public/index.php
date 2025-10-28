<?php
require_once '../config/config.php';
ob_start();

// Parse request
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Simple routing
switch ($segments[0]) {
	case '':
	case 'posts':
		require_once '../controllers/PostController.php';
		$controller = new PostController();
		$controller->recent();
		break;

	case 'post':
		require_once '../controllers/PostController.php';
		$controller = new PostController();
		$controller->show($segments[1]);
		break;

	case 'tags':
		require_once '../controllers/TagController.php';
		$controller = new TagController();
		if (isset($_GET['search'])) {
			$controller->search();
		}
		break;

	case 'upload':
		require_once __DIR__ . '/../controllers/UploadController.php';
		$controller = new UploadController();
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$controller->store();
		} else {
			$controller->form();
		}
		break;

	case 'profile':
		require_once '../controllers/ProfileController.php';
		$controller = new ProfileController();

		// /profile/invite
		if (isset($segments[1]) && $segments[1] === 'invite') {
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				if (isset($segments[2]) && $segments[2] === 'delete' && isset($segments[3])) {
					$controller::deleteInvite((int)$segments[3]);
				} else {
					$controller::createInvite();
				}
			} else {
				http_response_code(405);
			}
			break;
		}
		// /profile
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$controller::update();
		} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$controller::show();
		}
		break;

	case 'login':
		require_once '../controllers/AuthController.php';
		$controller = new AuthController();
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$controller::login();
		} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$controller::showLogin();
		}
		break;

	case 'register':
		require_once '../controllers/AuthController.php';
		$controller = new AuthController();
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$controller::register();
		} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
			$controller::showRegister();
		}
		break;

	case 'logout':
		require_once '../controllers/AuthController.php';
		$controller = new AuthController();
		$controller::logout();
		break;

	case 'admin':
		require_once '../controllers/AdminController.php';
		$controller = new AdminController();
		$controller::handle();
		break;

	default:
		require_once '../controllers/ErrorController.php';
		$controller = new ErrorController();
		$controller->notFound();
		break;
}

if (getenv('APP_DEBUG') !== false) {
	send_timings_header();
}
ob_end_flush();
