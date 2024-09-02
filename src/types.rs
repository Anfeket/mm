use chrono::Datelike;
use core::fmt;
use rand::Rng;
use std::{fmt::Display, str::FromStr};

#[derive(Debug)]
pub struct Post {
    pub id: PostId,
    pub description: String,
    pub post_type: PostType,
    pub tags: Vec<String>,
    pub rating: i32,
    pub post_date: i64,
    pub file: String,
}
impl Post {
    pub fn upvote(&mut self) {
        self.rating += 1
    }
    pub fn downvote(&mut self) {
        self.rating -= 1
    }
    pub fn id(&self) -> &str {
        &self.id.0
    }
    pub fn add_tag(&mut self, tag: &String) -> Result<(), PostError> {
        if self.tags.contains(tag) {
            Err(PostError::TagAlreadyExists(tag.into()))
        } else {
            self.tags.push(tag.into());
            Ok(())
        }
    }
    pub fn remove_tag(&mut self, tag: &String) -> Result<(), PostError> {
        if let Some(x) = self.tags.iter().position(|x| x == tag) {
            self.tags.swap_remove(x);
            Ok(())
        } else {
            Err(PostError::TagNotFound(tag.into()))
        }
    }

    pub fn new(description: String, post_type: PostType, file: String) -> Self {
        Self {
            id: PostId::new(),
            description,
            post_type,
            tags: Vec::new(),
            rating: 0,
            post_date: chrono::Utc::now().timestamp(),
            file,
        }
    }
}

#[derive(Debug, PartialEq, Clone)]
pub struct PostId(pub String);
impl PostId {
    pub fn new() -> Self {
        let date = Self::generate_date();
        let salt = Self::generate_salt(5);
        Self(date + &salt)
    }

    fn generate_date() -> String {
        let current_date = chrono::Utc::now();
        let year = current_date.year() % 100;
        let day = current_date.ordinal();
        format!("{}{}", year, day)
    }

    fn generate_salt(length: usize) -> String {
        const SALT_ALPHABET: &[u8] = b"abcdefghijklmnopqrstuvwxyz0123456789";
        (0..length)
            .map(|_| SALT_ALPHABET[rand::thread_rng().gen_range(0..SALT_ALPHABET.len())] as char)
            .collect()
    }
}
impl Display for PostId {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        write!(f, "{}", self.0)
    }
}

#[derive(Debug, PartialEq)]
pub enum PostType {
    Image,
    Video,
    Other,
}
impl PostType {
    pub fn as_str(&self) -> &str {
        match self {
            PostType::Image => "Image",
            PostType::Video => "Video",
            PostType::Other => "Other",
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

#[derive(Debug)]
pub enum PostError {
    PostNotFound(PostId),
    DuplicatePostId(PostId),
    TagAlreadyExists(String),
    TagNotFound(String),
    InvalidPostType(String),

    DatabaseError(String),
}
impl fmt::Display for PostError {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PostError::PostNotFound(id) => write!(f, "Post not found: {}", id),
            PostError::DuplicatePostId(id) => write!(f, "Duplicate Post Id: {}", id),
            PostError::TagAlreadyExists(tag) => write!(f, "Tag already exists: {}", tag),
            PostError::TagNotFound(tag) => write!(f, "Tag not found: {}", tag),
            PostError::InvalidPostType(post_type) => write!(f, "InvalidPostType: {}", post_type),
            PostError::DatabaseError(err) => write!(f, "Database Error: {}", err),
        }
    }
}
