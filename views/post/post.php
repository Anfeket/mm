<div id="main">
	<aside id="sidebar">
		<section id="tag-list">
			<h3>Artist</h3>
			<ul id="artist-tag-list">
				<?php foreach ($tags['artist'] as $tag): ?>
					<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-artist">
							<?= htmlspecialchars($tag) ?>
						</a></li>
				<?php endforeach; ?>
			</ul>

			<h3>Copyright</h3>
			<ul id="copyright-tag-list">
				<?php foreach ($tags['copyright'] as $tag): ?>
					<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-copyright">
							<?= htmlspecialchars($tag) ?>
						</a></li>
				<?php endforeach; ?>
			</ul>

			<h3>General</h3>
			<ul id="general-tag-list">
				<?php foreach ($tags['general'] as $tag): ?>
					<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-general">
							<?= htmlspecialchars($tag) ?>
						</a></li>
				<?php endforeach; ?>
			</ul>

			<h3>Meta</h3>
			<ul id="meta-tag-list">
				<?php foreach ($tags['meta'] as $tag): ?>
					<li><a href="/tag/<?= urlencode($tag) ?>" class="tag tag-meta">
							<?= htmlspecialchars($tag) ?>
						</a></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<section id="post-information">
			<h3>Information</h3>
			<dl>
				<dt>ID:</dt>
				<dd>
					<?= $post['id'] ?>
				</dd>
				<dt>Uploader:</dt>
				<dd>
					<?= htmlspecialchars($uploader['username']) ?>
				</dd>
				<dt>Size:</dt>
				<dd>
					<?= $post['file_size_human'] ?>
				</dd>
				<dt>Posted:</dt>
				<dd>
					<?= $post['created_at'] ?>
				</dd>
				<dt>Source:</dt>
				<dd>
					<?= $post['source'] ? htmlspecialchars($post['source']) : 'N/A' ?>
				</dd>
				<dt>Score:</dt>
				<dd>
					<?= $post['score'] ?>
				</dd>
				<dt>Favorites</dt>
				<dd>
					<?= $post['favorites_count'] ?>
				</dd>
			</dl>
		</section>
	</aside>
	<main id="mainContent">
		<article>
			<div id="post-actions">
				<a href="<?= $post['file_path'] ?>" download class="button">Download</a>
				<?php if (Auth::is_logged_in() && User::has_permission($_SESSION['user_id'], 'delete_post')): ?>
					<form action="/admin/delete-post" method="POST"
						onsubmit="return confirm('Are you sure you want to delete this post?');" style="display:inline;">
						<input type="hidden" name="post_id" value="<?= $post['id'] ?>">
						<button type="submit" class="button button-danger">Delete Post</button>
					</form>
				<?php endif; ?>
			</div>
			<div id="post-image">
				<?php if ($post['post_type'] === 'image'): ?>
					<img src="<?= htmlspecialchars($post['file_path']) ?>" alt="Post #<?= $post['id'] ?>" <?= $post['width']
																												? 'width="' . $post['width'] . '"' : '' ?>
						<?= $post['height'] ? 'height="' . (int)$post['height'] . '"' : '' ?>>
				<?php elseif ($post['post_type'] === 'video'): ?>
					<video src="<?= htmlspecialchars($post['file_path']) ?>" alt="Post #<?= $post['id'] ?>" controls
						<?= $post['width'] ? 'width="' . $post['width'] . '"' : '' ?>
						<?= $post['height'] ? 'height="' . (int)$post['height'] . '"' : '' ?>>
					<?php endif; ?>
			</div>
			<nav id="post-pagination">
				<?php if (Post::getPrevId($post['id'])): ?>
					<a href="<?= Post::getPrevId($post['id']) ?>" class="prev">&lt;</a>
				<?php endif; ?>
				<?= $post['id'] ?>
				</li>
				<?php if (Post::getNextId($post['id'])): ?>
					<a href="<?= Post::getNextId($post['id']) ?>" class="next">&gt;</a>
				<?php endif; ?>
			</nav>
			<div id="post-description">
				<h3>Description</h3>
				<?= $post['description'] ?>
			</div>
			<div id="post-comments">
				<h3>Comments</h3>
			</div>
		</article>
	</main>
</div>
<script>
	document.addEventListener('keydown', e => {
		const prev = document.querySelector('a.prev');
		const next = document.querySelector('a.next');

		if (['ArrowLeft', 'h'].includes(e.key) && prev) window.location = prev.href;
		if (['ArrowRight', 'l'].includes(e.key) && next) window.location = next.href;
	});
</script>
