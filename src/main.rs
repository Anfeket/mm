use std::env;

use db::Database;
use types::Post;

pub mod db;
pub mod types;

fn main() {
    let path = env::var("DB_PATH").unwrap_or(".\\mm.db".to_string());
    let conn = rusqlite::Connection::open(path).unwrap();
    let mut db = db::rusqlite::RusqliteDatabase::new(conn).unwrap();
    let post = Post::new(
        "test".to_string(),
        types::PostType::Other,
        "cokc.mp4".to_string(),
    );
    db.add_post(&post).unwrap();
    let posts = db.get_posts().unwrap();
    println!(
        "There are {} posts: {:?}",
        posts.len(),
        posts.iter().map(|id| &id.0).collect::<Vec<&String>>()
    );
    let get = db.get_post(&post.id).unwrap();
    println!("{:?}", get);
}
