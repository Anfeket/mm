<main id="post-index">
    <h2>Recent Posts</h2>
    <div class="post-grid">
        <?php foreach ($posts as $post): ?>
            <div class="post-thumb">
                <a href="/post/<?= $post['id'] ?>">
                    <img src="<?= htmlspecialchars($post['file_path']) ?>"
                         alt="Post #<?= $post['id'] ?>">
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
