use crate::types::{Post, PostId};
use ::rusqlite::Connection;

pub mod rusqlite;

pub trait Database {
    type Error: std::fmt::Debug;

    // Post operations
    fn add_post(&mut self, post: &Post) -> Result<(), Self::Error>;
    fn get_post(&self, id: &PostId) -> Result<Option<Post>, Self::Error>;
    fn get_posts(&self) -> Result<Vec<PostId>, Self::Error>;
    fn delete_post(&mut self, id: &PostId) -> Result<(), Self::Error>;

    // Post tag operations
    fn add_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error>;
    fn remove_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error>;
    fn get_post_tags(&self, id: &PostId) -> Result<Vec<String>, Self::Error>;

    // Post rating operations
    fn upvote_post(&mut self, id: &PostId) -> Result<(), Self::Error>;
    fn downvote_post(&mut self, id: &PostId) -> Result<(), Self::Error>;
    fn get_post_rating(&self, id: &PostId) -> Result<i32, Self::Error>;

    // Tag operations
    fn add_tag(&mut self, tag: &str) -> Result<(), Self::Error>;
    fn remove_tag(&mut self, tag: &str) -> Result<(), Self::Error>;
    fn get_tags(&self) -> Result<Vec<String>, Self::Error>;

    // Connection managment
    fn new(conn: Connection) -> Result<Self, Self::Error>
    where
        Self: Sized;
}
