#![allow(unused_variables)]
use std::str::FromStr;

use crate::{
    db::Database,
    types::{Post, PostError, PostId, PostType},
};
use rusqlite::{params, Connection, OptionalExtension};

fn database_error(err: rusqlite::Error) -> PostError {
    PostError::DatabaseError(err.to_string())
}
pub struct RusqliteDatabase {
    conn: Connection,
}
impl Database for RusqliteDatabase {
    type Error = PostError;

    fn add_post(&mut self, post: &Post) -> Result<(), Self::Error> {
        let mut tag_ids: Vec<u64> = Vec::new();
        for tag in &post.tags {
            match self
                .conn
                .query_row("SELECT id FROM tags WHERE name = ?1", params![tag], |row| {
                    row.get(0)
                }) {
                Ok(tag_id) => tag_ids.push(tag_id),
                Err(rusqlite::Error::QueryReturnedNoRows) => {
                    return Err(PostError::TagNotFound(tag.into()))
                }
                Err(err) => return Err(database_error(err)),
            };
        }
        let tx = self.conn.transaction().map_err(database_error)?;
        tx.execute(
            "INSERT INTO posts (id, description, post_type, rating, post_date, file)
            VALUES (?1, ?2, ?3, ?4, ?5, ?6)",
            params![
                post.id(),
                post.description,
                post.post_type.as_str(),
                post.rating,
                post.post_date,
                post.file
            ],
        )
        .map_err(database_error)?;
        for tag in tag_ids {
            tx.execute(
                "INSERT INTO post_tags (post_id, tag_id)
                VALUES (?1, ?2)",
                (post.id(), tag),
            )
            .map_err(database_error)?;
        }
        tx.commit().map_err(database_error)?;
        Ok(())
    }

    fn get_post(&self, id: &PostId) -> Result<Option<Post>, Self::Error> {
        let post = self
            .conn
            .query_row(
                "SELECT id, description, post_type, rating, post_date, file
            FROM posts WHERE id = ?1",
                params![id.0],
                |row| {
                    Ok((
                        row.get::<_, String>(0)?,
                        row.get::<_, String>(1)?,
                        row.get::<_, String>(2)?,
                        row.get::<_, i32>(3)?,
                        row.get::<_, i64>(4)?,
                        row.get::<_, String>(5)?,
                    ))
                },
            )
            .optional()
            .map_err(database_error)?;
        if post.is_none() {
            return Ok(None);
        }
        let (id, description, post_type, rating, post_date, file) = post.unwrap();

        let post_type =
            PostType::from_str(&post_type).map_err(|err| PostError::InvalidPostType(post_type))?;
        let post_id = PostId(id.clone());
        let mut post = Post {
            id: post_id,
            description,
            post_type,
            tags: Vec::new(),
            rating,
            post_date,
            file,
        };

        let mut tag_ids: Vec<u64> = Vec::new();
        let mut stmt = self
            .conn
            .prepare("SELECT tag_id FROM post_tags WHERE post_id = ?1")
            .map_err(database_error)?;
        let tags_iter = stmt
            .query_map(params![id], |row| row.get(0))
            .map_err(database_error)?;
        for tag in tags_iter {
            tag_ids.push(tag.map_err(database_error)?)
        }
        for tag_id in tag_ids {
            let tag = self.conn.query_row(
                "SELECT name FROM tags WHERE id = ?1",
                params![tag_id],
                |row| -> Result<String, rusqlite::Error> { row.get(0) },
            );
            match tag {
                Ok(tag) => post.tags.push(tag),
                Err(rusqlite::Error::QueryReturnedNoRows) => {
                    return Err(PostError::TagNotFound(tag_id.to_string()))
                }
                Err(err) => return Err(PostError::DatabaseError(err.to_string())),
            }
        }
        Ok(Some(post))
    }

    fn get_posts(&self) -> Result<Vec<PostId>, Self::Error> {
        Ok(self
            .conn
            .prepare("SELECT id FROM posts")
            .map_err(database_error)?
            .query_map([], |row| Ok(PostId(row.get(0)?)))
            .map_err(database_error)?
            .collect::<Result<Vec<PostId>, rusqlite::Error>>()
            .map_err(database_error)?)
    }

    fn delete_post(&mut self, id: &PostId) -> Result<(), Self::Error> {
        let tx = self.conn.transaction().map_err(database_error)?;
        let post_deleted = tx
            .execute("DELETE FROM post_tags WHERE post_id = ?1", params![id.0])
            .map_err(database_error)?;
        let post = tx
            .execute("DELETE FROM posts WHERE id = ?1", params![id.0])
            .map_err(database_error)?;
        if post == 0 {
            Err(PostError::PostNotFound(id.clone()))
        } else if post > 1 {
            Err(PostError::DatabaseError("Multiple posts found!".into()))
        } else {
            tx.commit().map_err(database_error)
        }
    }

    fn add_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error> {
        if !self
            .conn
            .query_row(
                "SELECT EXISTS(SELECT 1 FROM posts WHERE id = ?1)",
                params![id.0],
                |row| row.get(0),
            )
            .map_err(database_error)?
        {
            return Err(PostError::PostNotFound(id.clone()));
        }

        let tx = self.conn.transaction().map_err(database_error)?;
        for tag in tags {
            let tag_id: u64 = tx
                .query_row("SELECT id FROM tags WHERE name = ?1", params![tag], |row| {
                    row.get(0)
                })
                .optional()
                .map_err(database_error)?
                .ok_or(PostError::TagNotFound(tag.to_string()))?;
            tx.execute(
                "INSERT INTO post_tags (post_id, tag_id) VALUES (?1, ?2)",
                params![id.0, tag_id],
            )
            .map_err(database_error)?;
        }
        tx.commit().map_err(database_error)?;
        Ok(())
    }

    fn remove_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error> {
        if !self
            .conn
            .query_row(
                "SELECT EXISTS(SELECT 1 FROM posts WHERE id = ?1)",
                params![id.0],
                |row| row.get(0),
            )
            .map_err(database_error)?
        {
            return Err(PostError::PostNotFound(id.clone()));
        }

        let tx = self.conn.transaction().map_err(database_error)?;
        for tag in tags {
            let tag_id: u64 = tx
                .query_row("SELECT id FROM tags WHERE name = ?1", params![tag], |row| {
                    row.get(0)
                })
                .optional()
                .map_err(database_error)?
                .ok_or(PostError::TagNotFound(tag.to_string()))?;
            tx.execute(
                "DELETE FROM post_tags WHERE post_id = ?1 AND tag_id = ?2",
                params![id.0, tag_id],
            )
            .map_err(database_error)?;
        }
        tx.commit().map_err(database_error)?;
        Ok(())
    }

    fn get_post_tags(&self, id: &PostId) -> Result<Vec<String>, Self::Error> {
        if !self
            .conn
            .query_row(
                "SELECT EXISTS(SELECT 1 FROM posts WHERE id = ?1)",
                params![id.0],
                |row| row.get(0),
            )
            .map_err(database_error)?
        {
            return Err(PostError::PostNotFound(id.clone()));
        }

        let mut tag_ids: Vec<u64> = Vec::new();
        let mut stmt = self
            .conn
            .prepare("SELECT tag_id FROM post_tags WHERE post_id = ?1")
            .map_err(database_error)?;
        let tags_iter = stmt
            .query_map(params![id.0], |row| row.get(0))
            .map_err(database_error)?;
        for tag in tags_iter {
            tag_ids.push(tag.map_err(database_error)?)
        }
        let mut tags = Vec::new();
        for tag_id in tag_ids {
            let tag = self.conn.query_row(
                "SELECT name FROM tags WHERE id = ?1",
                params![tag_id],
                |row| -> Result<String, rusqlite::Error> { row.get(0) },
            );
            match tag {
                Ok(tag) => tags.push(tag),
                Err(rusqlite::Error::QueryReturnedNoRows) => {
                    return Err(PostError::TagNotFound(tag_id.to_string()))
                }
                Err(err) => return Err(PostError::DatabaseError(err.to_string())),
            }
        }
        Ok(tags)
    }

    fn upvote_post(&mut self, id: &PostId) -> Result<(), Self::Error> {
        if self
            .conn
            .execute(
                "UPDATE posts SET rating = rating + 1 WHERE id = ?1",
                params![id.0],
            )
            .map_err(database_error)?
            != 1
        {
            Err(PostError::PostNotFound(id.clone()))
        } else {
            Ok(())
        }
    }

    fn downvote_post(&mut self, id: &PostId) -> Result<(), Self::Error> {
        if self
            .conn
            .execute(
                "UPDATE posts SET rating = rating - 1 WHERE id = ?1",
                params![id.0],
            )
            .map_err(database_error)?
            != 1
        {
            Err(PostError::PostNotFound(id.clone()))
        } else {
            Ok(())
        }
    }

    fn get_post_rating(&self, id: &PostId) -> Result<i32, Self::Error> {
        self.conn
            .query_row(
                "SELECT rating FROM posts WHERE id = ?1",
                params![id.0],
                |row| row.get(0),
            )
            .optional()
            .map_err(database_error)?
            .ok_or(PostError::PostNotFound(id.clone()))
    }

    fn add_tag(&mut self, tag: &str) -> Result<(), Self::Error> {
        if self
            .conn
            .query_row(
                "SELECT EXISTS(SELECT 1 FROM tags WHERE name = ?1)",
                params![tag],
                |row| row.get(0),
            )
            .map_err(database_error)?
        {
            return Err(PostError::TagAlreadyExists(tag.into()));
        };
        self.conn
            .execute("INSERT INTO tags (name) VALUES (?1)", params![tag])
            .map_err(database_error)?;
        Ok(())
    }

    fn remove_tag(&mut self, tag: &str) -> Result<(), Self::Error> {
        if !self
            .conn
            .query_row(
                "SELECT EXISTS(SELECT 1 FROM tags WHERE name = ?1)",
                params![tag],
                |row| row.get(0),
            )
            .map_err(database_error)?
        {
            return Err(PostError::TagAlreadyExists(tag.into()));
        };

        self.conn
            .execute("DELETE FROM tags (name) VALUES (?1)", params![tag])
            .map_err(database_error)?;
        Ok(())
    }

    fn new(conn: Connection) -> Result<Self, Self::Error>
    where
        Self: Sized,
    {
        // Posts table
        conn.execute(
            "CREATE TABLE IF NOT EXISTS posts (
            id TEXT PRIMARY KEY,
            description TEXT NOT NULL,
            post_type TEXT NOT NULL,
            rating INTEGER DEFAULT 0,
            post_date INTEGER NOT NULL,
            file TEXT NOT NULL )",
            (),
        )
        .map_err(database_error)?;
        // Tags table
        conn.execute(
            "CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL )",
            (),
        )
        .map_err(database_error)?;
        // Post tags table
        conn.execute(
            "CREATE TABLE IF NOT EXISTS post_tags (
            post_id TEXT NOT NULL,
            tag_id INTEGER NOT NULL,
            FOREIGN KEY(post_id) REFERENCES posts(id),
            FOREIGN KEY(tag_id) REFERENCES tags(id),
            PRIMARY KEY(post_id, tag_id) )",
            (),
        )
        .map_err(database_error)?;
        Ok(Self { conn })
    }

    fn get_tags(&self) -> Result<Vec<String>, Self::Error> {
        Ok(self
            .conn
            .prepare("SELECT name FROM tags")
            .map_err(database_error)?
            .query_map([], |row| Ok(row.get(0)?))
            .map_err(database_error)?
            .collect::<Result<Vec<String>, rusqlite::Error>>()
            .map_err(database_error)?)
    }
}

#[cfg(test)]
mod tests {
    use crate::{
        db::Database,
        types::{Post, PostType},
    };

    use super::RusqliteDatabase;

    fn new_test_db() -> impl Database {
        let conn = rusqlite::Connection::open_in_memory().unwrap();
        RusqliteDatabase::new(conn).unwrap()
    }

    #[test]
    fn add_post() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "test".into());
        db.add_post(&post).expect("Failed to add post");

        // Check for post in db
        let retrieved_post = db.get_post(&post.id).expect("Failed to get post");
        assert!(retrieved_post.is_some());
        let retrieved_post = retrieved_post.unwrap();
        assert_eq!(retrieved_post.description, post.description);
        assert_eq!(retrieved_post.post_type, post.post_type);
    }

    #[test]
    fn delete_post() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "test".into());
        db.add_post(&post).expect("Failed to add post");

        // Check for post in db
        let retrieved_post = db.get_post(&post.id).expect("Failed to get post");
        assert!(retrieved_post.is_some());

        // Delete post
        db.delete_post(&post.id).expect("Failed to delete post");

        // Test if still exists
        let deleted_post = db.get_post(&post.id).expect("Failed to get post");
        assert!(deleted_post.is_none())
    }

    #[test]
    fn add_tags_to_post() {
        let mut db = new_test_db();

        // Create post with tag 1
        let mut post = Post::new("Test post with tags".into(), PostType::Other, "test".into());
        post.add_tag(&"Test tag 1".into())
            .expect("Failed to add tag");

        // Add tag 1 to db
        db.add_tag("Test tag 1").expect("Failed to add tag");

        // Add post to db
        db.add_post(&post).expect("Failed to add post");

        // Create tag 2
        db.add_tag("Test tag 2").expect("Failed to add tag");

        // Add tag 2 to post in db
        db.add_post_tags(&post.id, &["Test tag 2"]).unwrap();

        // Test tags
        let retrieved_post = db.get_post(&post.id).expect("Failed to get post");
        assert!(retrieved_post.is_some());
        let retrieved_post = retrieved_post.unwrap();
        assert_eq!(retrieved_post.tags.len(), 2);
        assert!(retrieved_post.tags.contains(&"Test tag 1".into()));
        assert!(retrieved_post.tags.contains(&"Test tag 2".into()));
    }

    #[test]
    fn get_all_posts() {
        let mut db = new_test_db();

        // Create posts
        let post1 = Post::new("Test post 1".into(), PostType::Other, "test".into());
        let post2 = Post::new("Test post 2".into(), PostType::Other, "test".into());
        db.add_post(&post1).expect("Failed to add post 1");
        db.add_post(&post2).expect("Failed to add post 2");

        // Test for posts
        let ids = db.get_posts().expect("Failed to get posts");
        assert_eq!(ids.len(), 2);
        assert!(ids.contains(&post1.id));
        assert!(ids.contains(&post2.id));
    }

    #[test]
    fn vote_post() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "test".into());
        db.add_post(&post).expect("Failed to add post");

        // Upvote
        db.upvote_post(&post.id).expect("Failed to upvote");
        assert_eq!(
            db.get_post_rating(&post.id).expect("Failed to get rating"),
            1
        );

        // Downvote
        db.downvote_post(&post.id).expect("Failed to downvote");
        assert_eq!(
            db.get_post_rating(&post.id).expect("Failed to get rating"),
            0
        );

        // Downvote second time
        db.downvote_post(&post.id).expect("Failed to downvote");
        assert_eq!(
            db.get_post_rating(&post.id).expect("Failed to get rating"),
            -1
        );
    }

    #[test]
    fn get_tags() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "file".into());
        db.add_post(&post).expect("Failed to add post");

        // Add tags
        db.add_tag("Tag 1").expect("Failed to add tag 1");
        db.add_tag("Tag 2").expect("Failed to add tag 2");
        db.add_tag("Tag 3").expect("Failed to add tag 3");
        let tags = vec!["Tag 1", "Tag 2", "Tag 3"];
        db.add_post_tags(&post.id, &tags)
            .expect("Failed to add tags to post");

        // Get tags from post
        let post_tags = db.get_post_tags(&post.id).expect("Failed to get post tags");
        assert_eq!(post_tags.len(), 3);
        assert_eq!(post_tags, tags);
    }

    #[test]
    fn remove_tags_from_post() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "file".into());
        db.add_post(&post).expect("Failed to add post");

        // Add tags to post
        db.add_tag("Tag 1").expect("Failed to add tag 1");
        db.add_tag("Tag 2").expect("Failed to add tag 2");
        db.add_tag("Tag 3").expect("Failed to add tag 3");
        let tags = vec!["Tag 1", "Tag 2", "Tag 3"];
        db.add_post_tags(&post.id, &tags)
            .expect("Failed to add tags to post");

        // Test tags are in post
        let post_tags = db.get_post_tags(&post.id).expect("Failed to get post tags");
        assert_eq!(post_tags.len(), 3);
        assert_eq!(post_tags, tags);

        // Remove tag from post
        db.remove_post_tags(&post.id, &tags)
            .expect("Failed to remove tag from post");
        let post_tags = db.get_post_tags(&post.id).expect("Failed to get post tags");
        assert!(post_tags.is_empty());
    }

    #[test]
    fn test_empty_case() {
        let mut db = new_test_db();

        // Create post
        let post = Post::new("Test post".into(), PostType::Other, "file".into());

        // List all posts (none)
        let posts = db.get_posts().expect("Failed to get posts");
        assert!(posts.is_empty());

        // Grab non existent post
        let fake_post = db.get_post(&post.id).expect("Failed to get post");
        assert!(fake_post.is_none());

        // Delete non existent post
        db.delete_post(&post.id).expect_err("Deleted non existent post!");

        // Get tags (none)
        let tags = db.get_tags().expect("Failed to get tags");
        assert!(tags.is_empty());
        
        // Add tag to non existent post
        db.add_tag("Real tag").expect("Failed to add tag");
        db.add_post_tags(&post.id, &["Real tag"]).expect_err("Added tag to non existent post!");

        // Delete tag from non existent post
        db.remove_post_tags(&post.id, &["Real tag"]).expect_err("Removed tag from non existent post!");

        // Vote on non existent post
        db.upvote_post(&post.id).expect_err("Upvoted non existent post!");
        db.downvote_post(&post.id).expect_err("Downvoted non existent post!");
        db.get_post_rating(&post.id).expect_err("Got rating of a non existent post!");

        // Test existing post methods
        db.add_post(&post).expect("Failed to add post");

        // Add non existing tag to post
        db.add_post_tags(&post.id, &["Non existing tag"]).expect_err("Non existing tag added to post!");

        // Remove non existing tag from post
        db.remove_post_tags(&post.id, &["Non existing tag"]).expect_err("Non existing tag removed from post!");
    }
}
