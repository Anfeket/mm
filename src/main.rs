use std::env;

use db::Database;
use dotenv::dotenv;
use types::{PostType, UserRole};

pub mod db;
pub mod types;
pub mod web;

#[async_std::main]
async fn main() {
    dotenv().ok();
    let url = env::var("DATABASE_URL").unwrap();
    let db = Database::new(&url).await.unwrap();
    let anfeket = match db.get_user_by_name("Anfeket").await {
        Ok(user) => user,
        Err(_) => {
            db.create_user("Anfeket", "test", "test", Some(UserRole::Admin))
                .await
                .unwrap()
        }
    };
    let post = db
        .create_post(&anfeket.id, "test", &PostType::Image, "test", &0, None)
        .await
        .unwrap();
    let tag = db
        .create_tag("test", &types::TagCategory::Meta, None)
        .await
        .unwrap();
    db.add_tag_to_post(&post.id, &tag.id).await.unwrap();
}
