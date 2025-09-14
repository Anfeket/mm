<?php
class User
{
	public static function findByUsername($username)
	{
		global $pdo;
		$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->execute([$username]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function create($username, $email, $password_hash)
	{
		global $pdo;
		$stmt = $pdo->prepare("INSERT INTO users (username,email,password_hash) VALUES (?,?,?)");
		$stmt->execute([$username, $email, $password_hash]);
		return $pdo->lastInsertId();
	}

	public static function findById($id)
	{
		global $pdo;
		$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
		$stmt->execute([$id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function update($id, $username, $email, $password = null, $avatar = null)
	{
		global $pdo;

		$fields = [];
		$params = [];

		if ($username !== null) {
			$fields[] = "username = ?";
			$params[] = $username;
		}
		if ($email !== null) {
			$fields[] = "email = ?";
			$params[] = $email;
		}
		if ($password !== null) {
			$fields[] = "password_hash = ?";
			$params[] = password_hash($password, PASSWORD_DEFAULT);
		}
		if ($avatar !== null) {
			$fields[] = "avatar = ?";
			$params[] = $avatar;
		}

		if (empty($fields)) return false;

		$params[] = $id;
		$sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
		$stmt = $pdo->prepare($sql);
		return $stmt->execute($params);
	}

	public static function createInvite($userId)
	{
		global $pdo;
		require_login(); // make sure caller is logged in

		// Generate a random 16-char code
		$inviteCode = bin2hex(random_bytes(16));

		$stmt = $pdo->prepare("
        INSERT INTO invites (code, created_by) 
        VALUES (:code, :created_by)
    ");
		$stmt->execute([
			':code' => $inviteCode,
			':created_by' => $userId
		]);

		return $inviteCode; // return the code so you can show/copy it
	}
}
