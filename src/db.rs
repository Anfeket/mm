use std::sync::Arc;

use sqlx::postgres::PgPoolOptions;

use crate::types::{
    Comment, Error as PostErr, Post, PostTag, PostType, TagCategory, User, UserRole,
};

#[derive(Clone)]
pub struct Database {
    pool: Arc<sqlx::postgres::PgPool>,
}
impl Database {
    /// Connects to the database and returns the struct for database operations
    pub async fn new(url: &str) -> Result<Self, sqlx::Error> {
        let pool = PgPoolOptions::new()
            .max_connections(10)
            .connect(url)
            .await?;
        Ok(Self {
            pool: Arc::new(pool),
        })
    }

    // User methods
    pub async fn create_user(
        &self,
        username: &str,
        email: &str,
        password: &str,
        role: Option<UserRole>,
    ) -> Result<User, PostErr> {
        let user = sqlx::query!(r#"
            INSERT INTO users (username, email, password_hash, role) VALUES ($1, $2, $3, $4)
            RETURNING id, username, email, password_hash, role AS "role: UserRole", created_at, is_deleted
            "#,
            username, email, password, role as Option<UserRole>
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if err.is_foreign_key_violation() {
                    if let Some(constraint) = err.constraint() {
                        return match constraint {
                            "users_username_key" => PostErr::DuplicateUsername(username.into()),
                            "users_email_key" => PostErr::DuplicateEmail(email.into()),
                            _ => PostErr::DatabaseError(e)
                        }
                    }
                }
            }
            PostErr::DatabaseError(e)
        })?;
        let user = User {
            id: user.id as u32,
            username: user.username,
            email: user.email,
            password_hash: user.password_hash,
            user_role: user.role,
            created_at: user.created_at,
            is_deleted: user.is_deleted,
        };
        Ok(user)
    }
    pub async fn get_user_by_id(&self, id: &u32) -> Result<User, PostErr> {
        let user = sqlx::query!(r#"
            SELECT id, username, email, password_hash, role AS "role: UserRole", created_at, is_deleted
            FROM users WHERE id = $1;
            "#,
            *id as i64
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::UserNotFoundId(*id)
                }
                PostErr::DatabaseError(e)
            })?;
        let user = User {
            id: *id,
            username: user.username,
            email: user.email,
            password_hash: user.password_hash,
            user_role: user.role,
            created_at: user.created_at,
            is_deleted: user.is_deleted,
        };
        Ok(user)
    }
    pub async fn get_user_by_name(&self, username: &str) -> Result<User, PostErr> {
        let user = sqlx::query!(r#"
            SELECT id, username, email, password_hash, role AS "role: UserRole", created_at, is_deleted
            FROM users WHERE username = $1;
            "#, 
            username
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::UserNotFoundName(username.into())
                }
                PostErr::DatabaseError(e)
            })?;
        let user = User {
            id: user.id as u32,
            username: user.username,
            email: user.email,
            password_hash: user.password_hash,
            user_role: user.role,
            created_at: user.created_at,
            is_deleted: user.is_deleted,
        };
        Ok(user)
    }
    pub async fn delete_user(&self, id: &u32) -> Result<(), PostErr> {
        sqlx::query!("DELETE FROM users WHERE id = $1", *id as i32)
            .execute(&*self.pool)
            .await
            .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::UserNotFoundId(*id);
                }
                PostErr::DatabaseError(e)
            })?;
        Ok(())
    }
    pub async fn login(&self, username: &str, password_hash: &str) -> Result<User, PostErr> {
        let user = sqlx::query!(r#"
            SELECT id, username, email, password_hash, role AS "role: UserRole", created_at, is_deleted
            FROM users WHERE username = $1;
            "#, 
            username
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::UserNotFoundName(username.into())
                }
                PostErr::DatabaseError(e)
            })?;
        if password_hash != user.password_hash {
            return Err(PostErr::AuthFailedIncorrectPassword(password_hash.into()));
        };
        let user = User {
            id: user.id as u32,
            username: user.username,
            email: user.email,
            password_hash: user.password_hash,
            user_role: user.role,
            created_at: user.created_at,
            is_deleted: user.is_deleted,
        };
        Ok(user)
    }

    // Post methods
    pub async fn create_post(
        &self,
        author_id: &u32,
        description: &str,
        post_type: &PostType,
        file_size: &u64,
        mime_type: &str,
        parent_post_id: Option<&u32>,
    ) -> Result<Post, PostErr> {
        let post = sqlx::query!(
            "
            INSERT INTO posts (author_id, description, post_type, file_size, parent_post_id, mime_type)
            VALUES ($1, $2, $3, $4, $5, $6) RETURNING id, post_date
            ",
            *author_id as i64,
            description,
            post_type as &PostType,
            *file_size as i64,
            parent_post_id.map(|id| *id as i32),
            mime_type
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if err.is_foreign_key_violation() {
                    return PostErr::UserNotFoundId(*author_id);
                }
            }
            PostErr::DatabaseError(e)
        })?;
        let post = Post {
            id: post.id as u32,
            author_id: *author_id,
            description: description.into(),
            post_type: post_type.clone(),
            tag_ids: Vec::new(),
            rating: 0,
            post_date: post.post_date,
            is_deleted: false,
            file_size: *file_size,
            mime_type: mime_type.into(),
            parent_post: parent_post_id.copied(),
            children_posts: Vec::new(),
            comments: Vec::new(),
        };
        Ok(post)
    }
    pub async fn get_post_by_id(&self, id: &u32) -> Result<Post, PostErr> {
        let post = sqlx::query!(r#"
            SELECT 
                p.id, 
                p.author_id, 
                p.description, 
                p.post_type AS "post_type: PostType", 
                p.file_size, 
                p.parent_post_id, 
                p.post_date, 
                p.is_deleted,
                p.mime_type,
                COALESCE(SUM(v.vote), 0) AS "rating?",
                ARRAY_REMOVE(ARRAY_AGG(pt.tag_id), NULL) AS "tag_ids?",
                ARRAY_REMOVE(ARRAY_AGG(cp.id), NULL) AS "children_post_ids?",
                ARRAY_REMOVE(ARRAY_AGG(c.id), NULL) AS "comments?"
            FROM posts p
            LEFT JOIN post_tags pt ON pt.post_id = p.id
            LEFT JOIN posts cp ON cp.parent_post_id = p.id
            LEFT JOIN votes v ON v.post_id = p.id
            LEFT JOIN comments c ON c.post_id = p.id
            WHERE p.id = $1
            GROUP BY 
                p.id, p.author_id, p.description, p.post_type, p.file_size, p.parent_post_id, p.post_date, p.is_deleted;
            "#,
            *id as i32
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::PostNotFound(*id);
                }
                PostErr::DatabaseError(e)
            })?;
        let post = Post {
            id: *id,
            author_id: post.author_id as u32,
            description: post.description,
            post_type: post.post_type,
            tag_ids: post
                .tag_ids
                .unwrap_or_default()
                .into_iter()
                .map(|tag| tag as u32)
                .collect(),
            rating: post.rating.unwrap_or_default(),
            post_date: post.post_date,
            is_deleted: post.is_deleted,
            file_size: post.file_size as u64,
            mime_type: post.mime_type,
            parent_post: post.parent_post_id.map(|id| id as u32),
            children_posts: post
                .children_post_ids
                .unwrap_or_default()
                .into_iter()
                .map(|id| id as u32)
                .collect(),
            comments: post
                .comments
                .unwrap_or_default()
                .into_iter()
                .map(|id| id as u32)
                .collect(),
        };
        Ok(post)
    }
    pub async fn get_posts_by_user(&self, user_id: &u32) -> Result<Vec<u32>, PostErr> {
        let posts = sqlx::query!("SELECT id FROM posts WHERE author_id = $1", *user_id as i32)
            .fetch_all(&*self.pool)
            .await
            .map_err(PostErr::DatabaseError)?;
        let post_ids = posts.into_iter().map(|post| post.id as u32).collect();
        Ok(post_ids)
    }
    pub async fn get_posts_by_ids(&self, post_ids: &[u32]) -> Result<Vec<Post>, PostErr> {
        if post_ids.is_empty() {
            return Ok(Vec::new());
        };
        let ids: Vec<i32> = post_ids.iter().map(|&id| id as i32).collect();
        let posts = sqlx::query!(
            r#"
            SELECT 
                p.id, 
                p.author_id, 
                p.description, 
                p.post_type AS "post_type: PostType", 
                p.file_size, 
                p.parent_post_id, 
                p.post_date, 
                p.is_deleted,
                p.mime_type,
                COALESCE(SUM(v.vote), 0) AS rating,
                array_agg(pt.tag_id) AS tag_ids,
                array_agg(cp.id) AS children_post_ids,
                array_agg(c.id) AS comments
            FROM posts p
            LEFT JOIN post_tags pt ON pt.post_id = p.id
            LEFT JOIN posts cp ON cp.parent_post_id = p.id
            LEFT JOIN votes v ON v.post_id = p.id
            LEFT JOIN comments c ON c.post_id = p.id
            WHERE p.id = ANY($1)
            GROUP BY 
                p.id, p.author_id, p.description, p.post_type, p.file_size, p.parent_post_id, p.post_date, p.is_deleted, p.mime_type;
            "#,
            &ids
        )
            .fetch_all(&*self.pool)
            .await
            .map_err(PostErr::DatabaseError)?;
        let posts = posts
            .into_iter()
            .map(|post| Post {
                id: post.id as u32,
                author_id: post.author_id as u32,
                description: post.description,
                post_type: post.post_type,
                tag_ids: post
                    .tag_ids
                    .unwrap_or_default()
                    .into_iter()
                    .map(|tag| tag as u32)
                    .collect(),
                rating: post.rating.unwrap_or(0),
                post_date: post.post_date,
                is_deleted: post.is_deleted,
                file_size: post.file_size as u64,
                mime_type: post.mime_type,
                parent_post: post.parent_post_id.map(|id| id as u32),
                children_posts: post
                    .children_post_ids
                    .unwrap_or_default()
                    .into_iter()
                    .map(|id| id as u32)
                    .collect(),
                comments: post
                    .comments
                    .unwrap_or_default()
                    .into_iter()
                    .map(|id| id as u32)
                    .collect(),
            })
            .collect();
        Ok(posts)
    }
    pub async fn get_newest_posts(&self, n: &u32, offset: &u32) -> Result<Vec<u32>, PostErr> {
        let posts = sqlx::query!(
            "SELECT id FROM posts ORDER BY post_date DESC LIMIT $1 OFFSET $2",
            *n as i32,
            *offset as i32
        )
        .fetch_all(&*self.pool)
        .await
        .map_err(PostErr::DatabaseError)?;
        let post_ids = posts.into_iter().map(|post| post.id as u32).collect();
        Ok(post_ids)
    }
    pub async fn delete_post(&self, post_id: &u32) -> Result<(), PostErr> {
        sqlx::query!("DELETE FROM posts WHERE id = $1", *post_id as i32)
            .execute(&*self.pool)
            .await
            .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::PostNotFound(*post_id);
                }
                PostErr::DatabaseError(e)
            })?;
        Ok(())
    }
    pub async fn add_tag_to_post(&self, post_id: &u32, tag_id: &u32) -> Result<(), PostErr> {
        sqlx::query!(
            "
            INSERT INTO post_tags (post_id, tag_id)
            VALUES ($1, $2);
            ",
            *post_id as i32,
            *tag_id as i32,
        )
        .execute(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if err.is_unique_violation() {
                    return PostErr::TagAlreadyAddedToPost(*tag_id, *post_id);
                };
                if let Some(constraint) = err.constraint() {
                    return match constraint {
                        "post_tags_post_id_key" => PostErr::PostNotFound(*post_id),
                        "post_tags_tag_id_key" => PostErr::TagNotFoundId(*tag_id),
                        _ => PostErr::DatabaseError(e),
                    };
                };
            };
            PostErr::DatabaseError(e)
        })?;
        Ok(())
    }
    pub async fn remove_tag_from_post(&self, post_id: &u32, tag_id: &u32) -> Result<(), PostErr> {
        sqlx::query!(
            "
            DELETE FROM post_tags
            WHERE post_id = $1
            AND tag_id = $2
            ",
            *post_id as i32,
            *tag_id as i32
        )
        .execute(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::TagNotFoundInPost(*tag_id, *post_id);
            }
            PostErr::DatabaseError(e)
        })?;
        Ok(())
    }
    pub async fn get_comments_from_post(&self, post_id: &u32) -> Result<Vec<Comment>, PostErr> {
        let comments = sqlx::query!(
            "
            SELECT id, user_id, content, comment_date, is_deleted FROM comments
            WHERE post_id = $1
            ",
            *post_id as i32
        )
        .fetch_all(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::PostNotFound(*post_id);
            }
            PostErr::DatabaseError(e)
        })?;
        let comments = comments
            .into_iter()
            .map(|comment| Comment {
                id: comment.id as u32,
                user_id: comment.user_id as u32,
                post_id: *post_id,
                content: comment.content,
                date: comment.comment_date,
                is_deleted: comment.is_deleted,
            })
            .collect();
        Ok(comments)
    }
    pub async fn add_comment_to_post(
        &self,
        post_id: &u32,
        user_id: &u32,
        content: &str,
    ) -> Result<Comment, PostErr> {
        let comment = sqlx::query!(
            "
            INSERT INTO comments (user_id, post_id, content)
            VALUES ($1, $2, $3)
            RETURNING id, comment_date, is_deleted
            ",
            *user_id as i32,
            *post_id as i32,
            content
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if let Some(constraint) = err.constraint() {
                    match constraint {
                        "comments_user_id_key" => PostErr::UserNotFoundId(*user_id),
                        "comments_post_id_key" => PostErr::PostNotFound(*post_id),
                        _ => todo!(),
                    };
                };
            };
            PostErr::DatabaseError(e)
        })?;
        let comment = Comment {
            id: comment.id as u32,
            user_id: *user_id,
            post_id: *post_id,
            content: String::new(),
            date: comment.comment_date,
            is_deleted: false,
        };
        Ok(comment)
    }
    pub async fn get_post_mime_type(&self, post_id: &u32) -> Result<String, PostErr> {
        let post = sqlx::query!(
            r#"
            SELECT mime_type
            FROM posts
            WHERE id = $1
            "#,
            *post_id as i32
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::PostNotFound(*post_id);
            }
            PostErr::DatabaseError(e)
        })?;
        Ok(post.mime_type)
    }

    // Tag Methods
    pub async fn create_tag(
        &self,
        name: &str,
        category: &TagCategory,
        alias_tag_id: Option<&u32>,
    ) -> Result<PostTag, PostErr> {
        let tag = sqlx::query!(
            "
            INSERT INTO tags (name, category, alias_tag_id)
            VALUES ($1, $2, $3)
            RETURNING id, created_at
            ",
            name,
            category as &TagCategory,
            alias_tag_id.map(|id| *id as i32)
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if err.is_foreign_key_violation() {
                    return PostErr::DuplicateTagName(name.into());
                }
            }
            PostErr::DatabaseError(e)
        })?;
        let tag = PostTag {
            id: tag.id as u32,
            name: name.into(),
            category: category.clone(),
            posts: 0,
            created_at: tag.created_at,
            alias_tag_id: None,
            implies: Vec::new(),
        };
        Ok(tag)
    }
    pub async fn delete_tag(&self, tag_id: &u32) -> Result<(), PostErr> {
        sqlx::query!("DELETE FROM tags WHERE id = $1", *tag_id as i32)
            .execute(&*self.pool)
            .await
            .map_err(|e| {
                if let sqlx::Error::RowNotFound = e {
                    return PostErr::TagNotFoundId(*tag_id);
                }
                PostErr::DatabaseError(e)
            })?;
        Ok(())
    }
    pub async fn get_tag_by_id(&self, tag_id: &u32) -> Result<PostTag, PostErr> {
        let tag = sqlx::query!(
            r#"
            SELECT
                t.name,
                t.category AS "category: TagCategory",
                t.created_at,
                t.alias_tag_id,
                COALESCE(array_agg(ti.implies_tag_id), '{}') AS implies_tag_ids,
                COUNT(pt.post_id) AS post_count
            FROM tags t
            LEFT JOIN tag_implications ti ON ti.tag_id = t.id
            LEFT JOIN post_tags pt ON pt.tag_id = t.id
            WHERE t.id = $1
            GROUP BY t.id, t.name, t.category, t.created_at, t.alias_tag_id
            "#,
            *tag_id as i32
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::TagNotFoundId(*tag_id);
            }
            PostErr::DatabaseError(e)
        })?;
        let tag = PostTag {
            id: *tag_id,
            name: tag.name,
            category: tag.category,
            posts: tag.post_count.unwrap_or(0) as u32,
            created_at: tag.created_at,
            alias_tag_id: tag.alias_tag_id.map(|id| id as u32),
            implies: tag
                .implies_tag_ids
                .unwrap_or_default()
                .into_iter()
                .map(|id| id as u32)
                .collect(),
        };
        Ok(tag)
    }
    pub async fn get_tag_by_name(&self, tag_name: &str) -> Result<PostTag, PostErr> {
        let tag = sqlx::query!(
            r#"
            SELECT
                t.id,
                t.category AS "category: TagCategory",
                t.created_at,
                t.alias_tag_id,
                COALESCE(array_agg(ti.implies_tag_id), '{}') AS implies_tag_ids,
                COUNT(pt.post_id) AS post_count
            FROM tags t
            LEFT JOIN tag_implications ti ON ti.tag_id = t.id
            LEFT JOIN post_tags pt ON pt.tag_id = t.id
            WHERE t.name = $1
            GROUP BY t.id, t.name, t.category, t.created_at, t.alias_tag_id
            "#,
            tag_name
        )
        .fetch_one(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::TagNotFoundName(tag_name.into());
            }
            PostErr::DatabaseError(e)
        })?;
        let tag = PostTag {
            id: tag.id as u32,
            name: tag_name.into(),
            category: tag.category,
            posts: tag.post_count.unwrap_or(0) as u32,
            created_at: tag.created_at,
            alias_tag_id: tag.alias_tag_id.map(|id| id as u32),
            implies: tag
                .implies_tag_ids
                .unwrap_or_default()
                .into_iter()
                .map(|id| id as u32)
                .collect(),
        };
        Ok(tag)
    }
    pub async fn create_tag_implication(
        &self,
        tag_id: &u32,
        implies_tag_id: &u32,
    ) -> Result<(), PostErr> {
        sqlx::query!(
            "
            INSERT INTO tag_implications (tag_id, implies_tag_id)
            VALUES ($1, $2);
            ",
            *tag_id as i32,
            *implies_tag_id as i32
        )
        .execute(&*self.pool)
        .await
        .map_err(|e| {
            if let Some(err) = e.as_database_error() {
                if err.is_unique_violation() {
                    return PostErr::DuplicateImplication(*tag_id, *implies_tag_id);
                };
                if let Some(constraint) = err.constraint() {
                    return match constraint {
                        "tag_implications_tag_id_key" => PostErr::TagNotFoundId(*tag_id),
                        "tag_implications_implies_tag_id" => {
                            PostErr::TagNotFoundId(*implies_tag_id)
                        }
                        _ => PostErr::DatabaseError(e),
                    };
                };
            };
            PostErr::DatabaseError(e)
        })?;
        Ok(())
    }
    pub async fn delete_tag_implication(
        &self,
        tag_id: &u32,
        implies_tag_id: &u32,
    ) -> Result<(), PostErr> {
        sqlx::query!(
            "
            DELETE FROM tag_implications
            WHERE tag_id = $1
            AND implies_tag_id = $2
            ",
            *tag_id as i32,
            *implies_tag_id as i32
        )
        .execute(&*self.pool)
        .await
        .map_err(|e| {
            if let sqlx::Error::RowNotFound = e {
                return PostErr::ImplicationNotFound(*tag_id, *implies_tag_id);
            }
            PostErr::DatabaseError(e)
        })?;
        Ok(())
    }
}
