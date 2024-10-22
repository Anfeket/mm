CREATE TYPE user_role AS ENUM ('Admin', 'Moderator', 'User', 'Guest');
CREATE TYPE post_type AS ENUM  ('Image', 'Video', 'Other');
CREATE TYPE tag_category AS ENUM ('General', 'Genre', 'Copyright', 'Artist', 'Meta');

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
	role user_role NOT NULL DEFAULT 'User',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    author_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    description TEXT NOT NULL,
    post_type post_type NOT NULL,
    file_size BIGINT NOT NULL,
    parent_post_id INT REFERENCES posts(id) ON DELETE CASCADE,
    post_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	view_count BIGINT NOT NULL DEFAULT 0,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS tags (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    category tag_category NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	alias_tag_id INT REFERENCES tags(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tag_implications (
	tag_id INT REFERENCES tags(id) ON DELETE CASCADE,
	implies_tag_id INT REFERENCES tags(id) ON DELETE CASCADE,
	PRIMARY KEY (tag_id, implies_tag_id)
);

CREATE TABLE IF NOT EXISTS post_tags (
    post_id INT REFERENCES posts(id) ON DELETE CASCADE,
    tag_id INT REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (post_id, tag_id)
);

CREATE TABLE IF NOT EXISTS votes (
	id SERIAL PRIMARY KEY,
	user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
	post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
	vote SMALLINT NOT NULL,
	UNIQUE (user_id, post_id)
);

CREATE TABLE IF NOT EXISTS comments (
	id SERIAL PRIMARY KEY,
	user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
	post_id INT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
	content TEXT NOT NULL,
	comment_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	is_deleted BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS notifications (
	id SERIAL PRIMARY KEY,
	user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
	message TEXT NOT NULL,
	notification_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
