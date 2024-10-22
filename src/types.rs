use core::fmt;
use std::str::FromStr;

use chrono::NaiveDateTime;
use serde::{Deserialize, Serialize};
use thiserror::Error;

#[derive(Debug, Serialize)]
pub struct Post {
    pub id: u32,
    pub author_id: u32,
    pub description: String,
    pub post_type: PostType,
    pub tag_ids: Vec<u32>,
    pub rating: i64,
    pub post_date: NaiveDateTime,
    pub is_deleted: bool,
    pub file_size: u64,
    pub parent_post: Option<u32>,
    pub children_posts: Vec<u32>,
    pub comments: Vec<u32>
}

#[derive(Debug, PartialEq, sqlx::Type, Clone, Serialize)]
#[sqlx(type_name = "post_type")]
pub enum PostType {
    Image,
    Video,
    Other,
}
impl fmt::Display for PostType {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PostType::Image => write!(f, "Image"),
            PostType::Video => write!(f, "Video"),
            PostType::Other => write!(f, "Other"),
        }
    }
}
impl FromStr for PostType {
    type Err = String;

    fn from_str(s: &str) -> Result<Self, Self::Err> {
        match s.to_lowercase().as_str() {
            "image" => Ok(Self::Image),
            "video" => Ok(Self::Video),
            "other" => Ok(Self::Other),
            _ => Err(format!("Unknown post type! {}", s)),
        }
    }
}

#[derive(Error, Debug)]
pub enum Error {
    // Post Errors
    #[error("Post not found: {0}")]
    PostNotFound(u32),
    #[error("Duplicate post id: {0}")]
    DuplicatePostId(u32),
    #[error("Post deleted: {0}")]
    PostIsDeleted(u32),
    #[error("Tag already added to post: tag {0} in post {1}")]
    TagAlreadyAddedToPost(u32, u32),
    #[error("Tag not found in post: tag {0} in post {1}")]
    TagNotFoundInPost(u32, u32),

    // Tag Errors
    #[error("Duplicate tag: {0}")]
    DuplicateTagName(String),
    #[error("Duplicate tag: {0}")]
    DuplicateTagId(u32),
    #[error("Tag not found: {0}")]
    TagNotFoundId(u32),
    #[error("Tag not found: {0}")]
    TagNotFoundName(String),
    #[error("Duplicate implication: {0} to {1}")]
    DuplicateImplication(u32, u32),
    #[error("Implication not found: {0} to {1}")]
    ImplicationNotFound(u32, u32),

    // User Errors
    #[error("User not found: {0}")]
    UserNotFoundId(u32),
    #[error("User not found: {0}")]
    UserNotFoundName(String),
    #[error("Duplicate user id: {0}")]
    DuplicateUserId(u32),
    #[error("Duplicate username: {0}")]
    DuplicateUsername(String),
    #[error("Duplicate email: {0}")]
    DuplicateEmail(String),
    #[error("Invalid username: {0}")]
    InvalidUsername(String),
    #[error("Invalid password: {0}")]
    InvalidPassword(String),
    #[error("Invalid email: {0}")]
    InvalidEmail(String),

    // Auth Errors
    #[error("Authentication failed, incorrect password: {0}")]
    AuthFailedIncorrectPassword(String),
    #[error("Authentication failed, incorrect passkey: {0}")]
    AuthFailedIncorrectPasskey(u32),

    #[error("Database error: {0}")]
    DatabaseError(#[from] sqlx::Error),
}

#[derive(Debug, Serialize)]
pub struct User {
    pub id: u32,
    pub username: String,
    pub email: String,
    pub password_hash: String,
    pub user_role: UserRole,
    pub created_at: chrono::NaiveDateTime,
    pub is_deleted: bool,
}

#[derive(Debug, Clone, Copy, sqlx::Type, Serialize, Deserialize)]
#[sqlx(type_name = "user_role")]
pub enum UserRole {
    Admin,
    Moderator,
    User,
    Guest,
}
impl fmt::Display for UserRole {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            UserRole::Admin => write!(f, "Admin"),
            UserRole::Moderator => write!(f, "Moderator"),
            UserRole::User => write!(f, "User"),
            UserRole::Guest => write!(f, "Guest"),
        }
    }
}
impl FromStr for UserRole {
    type Err = String;

    fn from_str(s: &str) -> Result<Self, Self::Err> {
        match s.to_lowercase().as_str() {
            "admin" => Ok(UserRole::Admin),
            "moderator" => Ok(UserRole::Moderator),
            "user" => Ok(UserRole::User),
            "guest" => Ok(UserRole::Guest),
            _ => Err(format!("Unknown user type: {}", s)),
        }
    }
}

#[derive(Debug)]
pub struct PostTag {
    pub id: u32,
    pub name: String,
    pub category: TagCategory,
    pub posts: u32,
    pub created_at: NaiveDateTime,
    pub alias_tag_id: Option<u32>,
    pub implies: Vec<u32>,
}
impl PartialEq for PostTag {
    fn eq(&self, other: &Self) -> bool {
        self.id == other.id
    }
}
#[derive(Debug, PartialEq, sqlx::Type, Clone)]
#[sqlx(type_name = "tag_category")]
pub enum TagCategory {
    General,
    Genre,
    Copyright,
    Artist,
    Meta,
}
impl fmt::Display for TagCategory {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            TagCategory::General => write!(f, "General"),
            TagCategory::Genre => write!(f, "Genre"),
            TagCategory::Copyright => write!(f, "Copyright"),
            TagCategory::Artist => write!(f, "Artist"),
            TagCategory::Meta => write!(f, "Meta"),
        }
    }
}
impl FromStr for TagCategory {
    type Err = String;

    fn from_str(s: &str) -> Result<Self, Self::Err> {
        match s.to_lowercase().as_str() {
            "general" => Ok(TagCategory::General),
            "genre" => Ok(TagCategory::Genre),
            "copyright" => Ok(TagCategory::Copyright),
            "artist" => Ok(TagCategory::Artist),
            "meta" => Ok(TagCategory::Meta),
            _ => Err(format!("Unknown tag category: {}", s)),
        }
    }
}

#[derive(Debug)]
pub struct Comment {
    pub id: u32,
    pub user_id: u32,
    pub post_id: u32,
    pub content: String,
    pub date: NaiveDateTime,
    pub is_deleted: bool
}
