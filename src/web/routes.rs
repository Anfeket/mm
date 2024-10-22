use axum::{routing::{get, post}, Router};

use super::handlers::{create_post, get_post, get_post_media, login};

pub fn routes() -> Router {
    Router::new()
        .route("/login", post(login))
        .route("/post", post(create_post))
        .route("/post/:id", get(get_post))
        .route("/media/:id", get(get_post_media))
}
