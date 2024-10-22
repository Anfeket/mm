use axum::{routing::{get, post}, Router};

use super::handlers::{create_post, get_post, login};

pub fn routes() -> Router {
    Router::new()
        .route("/login", post(login))
        .route("/post", post(create_post))
        .route("/post/:id", get(get_post))
}
