use crate::types::{Passkey, Post, PostId, PostTag, PostType, User};
use ::rusqlite::Connection;

pub mod rusqlite;

pub trait Database {
    type Error: std::fmt::Debug;

    // Post operations
    fn add_post(&mut self, description: String, post_type: PostType, file: String, author_id: u64) -> Result<(), Self::Error>;
    fn get_post(&self, post_id: &PostId) -> Result<Option<Post>, Self::Error>;
    fn get_posts(&self) -> Result<Vec<PostId>, Self::Error>;
    fn delete_post(&mut self, post_id: &PostId) -> Result<(), Self::Error>;
    fn update_post(&mut self, post_id: &Post) -> Result<(), Self::Error>;
    fn restore_post(&mut self, post_id: &PostId) -> Result<(), Self::Error>;
    fn add_post_child(&mut self, post_id: &PostId, child_id: &PostId) -> Result<(), Self::Error>;
    fn get_post_children(&self, post_id: &PostId) -> Result<Vec<PostId>, Self::Error>;
    fn get_post_parent(&self, post_id: &PostId) -> Result<PostId, Self::Error>;
    fn get_posts_by_author(&self, author_id: u64) -> Result<Vec<PostId>, Self::Error>;

    // Post tag operations
    fn add_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error>;
    fn remove_post_tags(&mut self, id: &PostId, tags: &[&str]) -> Result<(), Self::Error>;
    fn get_post_tags(&self, id: &PostId) -> Result<Vec<String>, Self::Error>;

    // Post rating operations
    fn upvote_post(&mut self, id: &PostId) -> Result<(), Self::Error>;
    fn downvote_post(&mut self, id: &PostId) -> Result<(), Self::Error>;
    fn get_post_rating(&self, id: &PostId) -> Result<i32, Self::Error>;

    // Tag operations
    fn add_tag(&mut self, tag: &PostTag) -> Result<(), Self::Error>;
    fn remove_tag(&mut self, tag_id: u64) -> Result<(), Self::Error>;
    fn get_tag_by_id(&self, tag_id: u64) -> Result<(), Self::Error>;
    fn get_tag_by_name(&self, tag_name: String) -> Result<(), Self::Error>;
    fn get_tag_ids(&self) -> Result<Vec<u64>, Self::Error>;
    fn get_tags(&self) -> Result<Vec<PostTag>, Self::Error>;

    // User operations
    fn new_user(&mut self, user: User) -> Result<(), Self::Error>;
    fn get_user_by_id(&self, id: u64) -> Result<Option<User>, Self::Error>;
    fn get_user_by_username(&self, username: String) -> Result<Option<User>, Self::Error>;
    fn update_user_password(&mut self, id: u64, password: String) -> Result<(), Self::Error>;
    fn update_user_passkey(&mut self, id: u64, passkey: Passkey) -> Result<(), Self::Error>;
    fn delete_user(&mut self, id: u64) -> Result<(), Self::Error>;

    // Authentication
    fn auth_with_password(&self, username: String, password: String) -> Result<Option<User>, Self::Error>;
    fn auth_with_passkey(&self, passkey: Passkey) -> Result<Option<User>, Self::Error>;
    fn add_passkey(&mut self, user_id: u64, passkey: Passkey) -> Result<(), Self::Error>;
    fn get_passkey_by_id(&self, passkey_id: u64) -> Result<Passkey, Self::Error>;
    fn remove_passkey(&mut self, passkey_id: u64) -> Result<(), Self::Error>;

    // Connection managment
    fn new(conn: Connection) -> Result<Self, Self::Error>
    where
        Self: Sized;
}
