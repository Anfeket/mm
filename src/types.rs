use core::fmt;
use std::{fmt::Display, fs::write, str::FromStr};

#[derive(Debug)]
pub struct Post {
    pub id: PostId,
    pub author: User,
    pub description: String,
    pub post_type: PostType,
    pub tags: Vec<PostTag>,
    pub rating: i32,
    pub post_date: i64,
    pub file: String,
    pub is_deleted: bool,
    pub file_size: u64,
    pub parent_post: Option<PostId>,
    pub children_posts: Vec<PostId>,
}
impl Post {
    pub fn new(
        id: PostId,
        author: User,
        description: String,
        post_type: PostType,
        post_date: i64,
        file: String,
        file_size: u64,
        parent_post: Option<PostId>,
    ) -> Self {
        Self {
            id,
            author,
            description,
            post_type,
            tags: Vec::new(),
            rating: 0,
            post_date,
            file,
            is_deleted: false,
            file_size,
            parent_post,
            children_posts: Vec::new(),
        }
    }
}

#[derive(Debug, PartialEq, Clone)]
pub struct PostId(pub u64);
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
    // Post Errors
    PostNotFound(PostId),
    DuplicatePostId(PostId),
    InvalidPostType(String),
    PostIsDeleted(PostId),

    // Tag Errors
    TagAlreadyExists(String),
    TagNotFound(String),

    // User Errors
    UserNotFound(u64),
    DuplicateUserId(u64),
    UsernameAlreadyTaken(String),
    InvalidUsername(String),
    InvalidPassword(String),
    InvalidEmail(String),
    Unauthorized(u64),

    // Passkey Errors
    PasskeyNotFound(u64),
    DuplicatePasskey(u64),
    InvalidPasskeyFormat(String),
    PasskeyExpired(u64),
    PasskeyMismatch { user_id: u64, passkey_id: u64 },

    // Authentication Errors
    AuthFailedIncorrectPassword(String),
    AuthFailedIncorrectPasskey(u64),
    UserNotAuthenticated(u64),

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
            PostError::PostIsDeleted(post_id) => write!(f, "Post is deleted: {}", post_id),
            PostError::UserNotFound(user_id) => write!(f, "User not found {}", user_id),
            PostError::DuplicateUserId(user_id) => write!(f, "Duplicate User Id: {}", user_id),
            PostError::UsernameAlreadyTaken(username) => {
                write!(f, "Username already taken: {}", username)
            }
            PostError::InvalidUsername(username) => write!(f, "Invalid Username: {}", username),
            PostError::InvalidPassword(password) => write!(f, "Invalid Password: {}", password),
            PostError::InvalidEmail(email) => write!(f, "Invalid Email: {}", email),
            PostError::Unauthorized(user_id) => {
                write!(f, "User unauthorized for this action: {}", user_id)
            }
            PostError::PasskeyNotFound(passkey_id) => {
                write!(f, "Passkey not found: {}", passkey_id)
            }
            PostError::DuplicatePasskey(passkey_id) => {
                write!(f, "Duplicate Passkey: {}", passkey_id)
            }
            PostError::InvalidPasskeyFormat(format) => {
                write!(f, "Invalid Passkey format: {}", format)
            }
            PostError::PasskeyExpired(passkey_id) => write!(f, "Passkey expired: {}", passkey_id),
            PostError::PasskeyMismatch {
                user_id,
                passkey_id,
            } => write!(f, "Passkey mismatch: {} with user: {}", passkey_id, user_id),
            PostError::AuthFailedIncorrectPassword(password) => {
                write!(f, "Authentication failed, incorrect password: {}", password)
            }
            PostError::AuthFailedIncorrectPasskey(passkey_id) => write!(
                f,
                "Authentication failed, incorrect passkey: {}",
                passkey_id
            ),
            PostError::UserNotAuthenticated(user_id) => {
                write!(f, "User not authenticated: {}", user_id)
            }
        }
    }
}

#[derive(Debug)]
pub struct User {
    id: u64,
    username: String,
    email: String,
    password: Option<String>,
    passkeys: Vec<Passkey>,
    role: Role,
    created_at: i64,
    is_deleted: bool,
}

#[derive(Debug)]
pub enum Role {
    Admin,
    Moderator,
    User,
    Guest,
}

#[derive(Debug)]
pub struct Passkey {
    id: u64,
    user_id: u64,
    credential_id: u64,
    public_key: String,
    counter: u64,
}

#[derive(Debug, PartialEq)]
pub struct PostTag {
    id: u64,
    name: String,
    category: Option<String>,
    posts: u64,
    created_at: i64,
}
