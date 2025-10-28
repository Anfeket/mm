<?php require_once __DIR__ . '/../../models/Discord.php' ?>
<main>
	<h2>Admin commands</h2>
	<ul>
		<li>
			<h3>Send webhook</h3>
			<form action="/admin?c=send-webhook" method="POST">
				<label>Post id:
					<input type="text" name="post_id" required>
				</label>
				<button type="submit">Send</button>
			</form>
		</li>
	</ul>
	<h2>Stats</h2>
	<ul>
		<li>
			<p>Discord webhook found:
				<?= Discord::getWebhook() ?>
			</p>
		</li>
	</ul>
</main>
