CREATE TABLE roles (
	id SERIAL PRIMARY KEY,
	name VARCHAR(50) NOT NULL UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
	user_id BIGINT UNSIGNED NOT NULL,
	role_id BIGINT UNSIGNED NOT NULL,
	assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id, role_id),
	CONSTRAINT fk_userrole_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	CONSTRAINT fk_userrole_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
	INDEX idx_user_roles_role_id (role_id)
);

CREATE TABLE permissions (
	id SERIAL PRIMARY KEY,
	name VARCHAR(100) NOT NULL UNIQUE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
	role_id BIGINT UNSIGNED NOT NULL,
	permission_id BIGINT UNSIGNED NOT NULL,
	assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (role_id, permission_id),
	CONSTRAINT fk_roleperm_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
	CONSTRAINT fk_roleperm_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
	INDEX idx_role_permissions_permission_id (permission_id)
);

-- Migrate existing user roles
INSERT INTO roles (name) VALUES ('admin'), ('mod'), ('user');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON u.role = r.name;

-- Remove old role column from users table
ALTER TABLE users DROP COLUMN role;
