use std::env;

use db::Database;
use dotenv::dotenv;

pub mod db;
pub mod types;
pub mod web;

#[tokio::main]
async fn main() {
    dotenv().ok();
    let url = env::var("DATABASE_URL").unwrap();
    let db = Database::new(&url).await.unwrap();
    let user = if let Ok(user) = db.get_user_by_name("anfeket").await {
        user
    } else {
        db.create_user("anfeket", "test", "test", Some(types::UserRole::Admin))
            .await
            .unwrap()
    };
    println!("{:?}", user);

    let app = web::routes::routes().layer(axum::extract::Extension(db));

    let listener = tokio::net::TcpListener::bind("0.0.0.0:8000").await.unwrap();
    axum::serve(listener, app).await.unwrap()

}
