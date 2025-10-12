	<footer id="footer">
		<p>
			&copy; <?= date('Y') ?> mm -
			<a href="<?= REPO_URL ?>commit/<?= urlencode(BUILD_HASH) ?>"
				target="_blank"
				rel="noopener noreferrer">
				<?= htmlspecialchars(BUILD_HASH) ?>
			</a>
		</p>
	</footer>
	</body>

	</html>
