<main id="login">
	<h2>Login</h2>
	<?php if (!empty($error)): ?>
		<p style="color:red;">
			<?= htmlspecialchars($error) ?>
		</p>
	<?php endif; ?>
	<form action="/login" method="post">
		<label>Username: <input type="text" name="username" required></label>
		<label>Password: <input type="password" name="password" required></label>
		<button type="submit">Login</button>
	</form>
</main>
