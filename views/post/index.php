<main id="post-index">
	<h2>Recent Posts</h2>
	<div class="post-grid">
		<?php foreach ($posts as $post): ?>
			<div class="post-thumb<?= $post['post_type'] === 'video' ? ' post-video' : '' ?>">
				<a href="/post/<?= $post['id'] ?>">
					<img src="<?= htmlspecialchars($post['thumb_path']) ?>"
						alt="Post #<?= $post['id'] ?>">
				</a>
				<div class="vote-box">
					<a href="/post/<?= $post['id'] ?>/vote/up" class="vote vote-up">▲</a>
					<span class="score"><?= (int)$post['score'] ?></span>
					<a href="/post/<?= $post['id'] ?>/vote/down" class="vote vote-down">▼</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</main>
