<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Invite.php';
require_once __DIR__ . '/../models/Auth.php';

class AuthController
{
	public static function showLogin()
	{
		$title = "Login - mm";
		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/auth/login.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	public static function login()
	{
		session_start();
		$user = User::findByUsername($_POST['username']);

		if ($user && password_verify($_POST['password'], $user['password_hash'])) {
			session_regenerate_id(true); // prevent fixation
			$_SESSION['user_id'] = $user['id'];
			Auth::log_ip($user['id']);
			header("Location: /");
			exit;
		} else {
			$error = "Invalid credentials";
			self::showLogin();
		}
	}

	public static function showRegister()
	{
		$invite = $_GET['invite'] ?? '';
		$title = "Register - mm";

		include __DIR__ . '/../views/layout/head.php';
		include __DIR__ . '/../views/layout/header.php';
		include __DIR__ . '/../views/auth/register.php';
		include __DIR__ . '/../views/layout/footer.php';
	}

	public static function register()
	{
		global $pdo;

		$invite = $_POST['invite'] ?? '';
		if (!$invite) {
			die("Invite required.");
		}

		// Check invite validity
		$stmt = $pdo->prepare("SELECT * FROM invites WHERE code = ? AND used_by IS NULL");
		$stmt->execute([$invite]);
		$inviteRow = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$inviteRow) {
			die("Invalid or already used invite.");
		}

		// Create user
		$hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
		$userId = User::create($_POST['username'], $_POST['email'], $hash);

		// Mark invite as used
		Invite::useCode($invite, $userId);

		header("Location: /login");
		exit;
	}

	public static function logout()
	{
		session_start();
		session_destroy();
		header("Location: /");
		exit;
	}
}
