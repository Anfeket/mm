-- Users
CREATE TABLE users (
	id SERIAL PRIMARY KEY,
	username VARCHAR(100) NOT NULL UNIQUE,
	email VARCHAR(255) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	avatar VARCHAR(255) DEFAULT NULL,
	role ENUM('admin','mod','user','guest') NOT NULL DEFAULT 'user',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	deleted_at TIMESTAMP NULL,
	INDEX idx_users_username (username)
);

-- Posts
CREATE TABLE posts (
	id SERIAL PRIMARY KEY,
	author_id BIGINT UNSIGNED NOT NULL,
	parent_post_id BIGINT UNSIGNED DEFAULT NULL,
	post_type ENUM('image','video') NOT NULL,
	mime_type VARCHAR(100),
	file_hash CHAR(32) NOT NULL,
	file_ext VARCHAR(10) NOT NULL,
	original_file_name VARCHAR(255),
	file_size BIGINT UNSIGNED NOT NULL,
	width INT UNSIGNED DEFAULT NULL,
	height INT UNSIGNED DEFAULT NULL,
	duration INT UNSIGNED DEFAULT NULL,
	description TEXT,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	view_count INT UNSIGNED DEFAULT 0,
	votes_count INT UNSIGNED DEFAULT 0,
	score INT UNSIGNED DEFAULT 0,
	favorites_count INT UNSIGNED DEFAULT 0,
	comments_count INT UNSIGNED DEFAULT 0,
	deleted_at TIMESTAMP NULL,
	CONSTRAINT fk_posts_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_posts_parent FOREIGN KEY (parent_post_id) REFERENCES posts(id) ON DELETE SET NULL,
	INDEX idx_posts_author_id (author_id),
	INDEX idx_posts_post_date (created_at),
	INDEX idx_posts_file_hash (file_hash)
);

-- Tags
CREATE TABLE tags (
	id SERIAL PRIMARY KEY,
	name VARCHAR(100) NOT NULL UNIQUE,
	alias_tag_id BIGINT UNSIGNED NULL,
	post_count INT UNSIGNED DEFAULT 0,
	category ENUM('artist','copyright','genre','general','meta') NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_tag_alias FOREIGN KEY (alias_tag_id) REFERENCES tags(id) ON DELETE SET NULL,
	INDEX idx_tags_name (name)
);

-- Tag implications
CREATE TABLE tag_implications (
	tag_id BIGINT UNSIGNED NOT NULL,
	implied_tag_id BIGINT UNSIGNED NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (tag_id, implied_tag_id),
	CONSTRAINT fk_tagimp_tag_id FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
	CONSTRAINT fk_tagimp_implied_tag_id FOREIGN KEY (implied_tag_id) REFERENCES tags(id) ON DELETE CASCADE,
	UNIQUE INDEX uq_tag_implications (tag_id, implied_tag_id)
);

-- Post tags
CREATE TABLE post_tags (
	post_id BIGINT UNSIGNED NOT NULL,
	tag_id BIGINT UNSIGNED NOT NULL,
	added_by BIGINT UNSIGNED NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (post_id, tag_id),
	CONSTRAINT fk_posttag_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
	CONSTRAINT fk_posttag_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
	CONSTRAINT fk_posttag_added_by FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
	INDEX uq_post_tags (post_id, tag_id),
	INDEX idx_post_tags_tag_id (tag_id)
);

-- Votes
CREATE TABLE votes (
	post_id BIGINT UNSIGNED NOT NULL,
	user_id BIGINT UNSIGNED NOT NULL,
	vote TINYINT NOT NULL,
	PRIMARY KEY (post_id, user_id),
	CONSTRAINT fk_vote_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
	CONSTRAINT fk_vote_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CHECK (vote in (-1, 1))
);

-- Favorites
CREATE TABLE favorites (
	post_id BIGINT UNSIGNED NOT NULL,
	user_id BIGINT UNSIGNED NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (post_id, user_id),
	CONSTRAINT fk_fav_post_id FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
	CONSTRAINT fk_fav_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	INDEX uq_fav_user_post (user_id, post_id),
	INDEX idx_fav_user_id (user_id)
);

-- Comments
CREATE TABLE comments (
	id SERIAL PRIMARY KEY,
	post_id BIGINT UNSIGNED NOT NULL,
	user_id BIGINT UNSIGNED NOT NULL,
	content TEXT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	deleted_at TIMESTAMP NULL,
	CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
	INDEX idx_comments_post_id (post_id),
	INDEX idx_comments_user_id (user_id)
);

-- Notifications
CREATE TABLE notifications (
	id SERIAL PRIMARY KEY,
	user_id BIGINT UNSIGNED NOT NULL,
	message TEXT NOT NULL,
	notification_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	is_read BOOLEAN NOT NULL,
	CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	INDEX idx_notifications_user_id (user_id)
);
