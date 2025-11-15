INSERT INTO permissions (name) VALUES 
('create_post'),
('edit_post'),
('delete_post'),
('ban_user'),
('access_admin_panel');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
JOIN permissions p ON 
	(r.name = 'admin' AND p.name IN ('create_post', 'edit_post', 'delete_post', 'ban_user', 'access_admin_panel')) OR
	(r.name = 'mod' AND p.name IN ('create_post', 'edit_post', 'delete_post', 'ban_user')) OR
	(r.name = 'user' AND p.name = 'create_post');
