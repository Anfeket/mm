<?php
require_once __DIR__ . '/../../models/Auth.php';
$user = current_user();
?>
<header id="header">
	<div id="top">
		<h1 id="site-name"><a href="/">mm</a></h1>

		<div id="account-box">
			<?php if ($user): ?>
				<a href="/profile" class="profile-link">
					<?php if ($user['avatar']): ?>
						<img src="<?= htmlspecialchars($user['avatar']) ?>"
							alt="Avatar" width="24" height="24"
							class="avatar">
					<?php endif; ?>
					<?= htmlspecialchars($user['username']) ?>
				</a>
				<a href="/logout" class="logout-link"
					onclick="return confirm('Are you sure you want to log out?')"
					title="Logout">
					<svg class="chevron" xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="9 6 15 12 9 18" />
					</svg>
				</a>
			<?php else: ?>
				<a href="/login" class="auth-link login-link">Login</a>
				<a href="/register" class="auth-link register-link">Register</a>
			<?php endif; ?>
		</div>
	</div>

	<nav>
		<a href="/posts">Posts</a>
		<a href="#">Tags</a>
		<a href="#">Artists</a>
		<a href="#">Pools</a>
		<a href="#">Wiki</a>
		<a href="#">Forum</a>
		<?php if ($user): ?>
		<a href="/upload">Upload Post</a>
		<?php endif; ?>
	</nav>
</header>
