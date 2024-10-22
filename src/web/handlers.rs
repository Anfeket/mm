use crate::types::Post;
use axum::{
    extract::{Multipart, Path},
    http::StatusCode,
    response::IntoResponse,
    Extension, Json,
};
use serde::{Deserialize, Serialize};

use crate::{
    db::Database,
    types::{PostType, User},
};

use super::save_media;

#[derive(Serialize)]
pub struct ErrorResponse {
    message: String,
}
impl From<&str> for ErrorResponse {
    fn from(value: &str) -> Self {
        ErrorResponse {
            message: value.to_string(),
        }
    }
}

#[derive(Deserialize)]
pub struct LoginRequest {
    username: String,
    password: String,
}
#[derive(Serialize)]
struct LoginResponse {
    user: User,
}
pub async fn login(
    Extension(db): Extension<Database>,
    Json(json): Json<LoginRequest>,
) -> Result<impl IntoResponse, (StatusCode, Json<ErrorResponse>)> {
    match db.login(&json.username, &json.password).await {
        Ok(user) => Ok((StatusCode::OK, Json(LoginResponse { user }))),
        Err(err) => {
            let (status, msg) = match err {
                crate::types::Error::UserNotFoundName(_) => {
                    (StatusCode::NOT_FOUND, "User not found")
                }
                crate::types::Error::AuthFailedIncorrectPassword(_) => {
                    (StatusCode::UNAUTHORIZED, "Incorrect password")
                }
                crate::types::Error::DatabaseError(_) => {
                    (StatusCode::INTERNAL_SERVER_ERROR, "Internal server error")
                }
                _ => unreachable!(),
            };
            Err((
                status,
                Json(ErrorResponse {
                    message: msg.into(),
                }),
            ))
        }
    }
}

#[derive(Serialize)]
struct CreatePostResponse {
    post: Post,
}
pub async fn create_post(
    Extension(db): Extension<Database>,
    mut multipart: Multipart,
) -> Result<impl IntoResponse, (StatusCode, Json<ErrorResponse>)> {
    let mut data: axum::body::Bytes = axum::body::Bytes::new();
    let mut content_type = String::new();
    let mut post_type = None;
    let mut author_id = None;
    let mut description = None;
    while let Ok(Some(field)) = multipart.next_field().await {
        if let Some(name) = field.name() {
            match name {
                "media" => {
                    content_type = field
                        .content_type()
                        .ok_or_else(|| {
                            (StatusCode::BAD_REQUEST, Json("Missing content type".into()))
                        })?
                        .to_string();
                    post_type = match content_type.as_str() {
                        "image/png" | "image/jpeg" => Some(PostType::Image),
                        "image/mp4" => Some(PostType::Video),
                        _ => Some(PostType::Other),
                    };
                    content_type = content_type.clone();
                    data = field.bytes().await.map_err(|e| {
                        (
                            StatusCode::INTERNAL_SERVER_ERROR,
                            Json(format!("Failed to read data: {:?}", e).as_str().into()),
                        )
                    })?;
                }
                "author_id" => {
                    let data = field.text().await.map_err(|e| {
                        (
                            StatusCode::INTERNAL_SERVER_ERROR,
                            Json(format!("Failed to read data: {:?}", e).as_str().into()),
                        )
                    })?;
                    let id = data.parse().map_err(|e| {
                        (
                            StatusCode::BAD_REQUEST,
                            Json(format!("Bad author id format: {}", e).as_str().into()),
                        )
                    })?;
                    author_id = Some(id)
                }
                "description" => {
                    let data = field.text().await.map_err(|e| {
                        (
                            StatusCode::INTERNAL_SERVER_ERROR,
                            Json(format!("Failed to read data: {:?}", e).as_str().into()),
                        )
                    })?;
                    description = Some(data)
                }
                _ => {}
            }
        }
    }
    let (author_id, description, post_type) = {
        let author_id =
            author_id.ok_or_else(|| (StatusCode::BAD_REQUEST, Json("Missing author_id".into())))?;
        let description = description
            .ok_or_else(|| (StatusCode::BAD_REQUEST, Json("Missing description".into())))?;
        let post_type =
            post_type.ok_or_else(|| (StatusCode::BAD_REQUEST, Json("Missing post_type".into())))?;
        (author_id, description, post_type)
    };
    let post = match db
        .create_post(
            &author_id,
            &description,
            &post_type,
            &(data.len() as u64),
            None,
        )
        .await
    {
        Ok(post) => post,
        Err(err) => {
            let (status, error) = match err {
                crate::types::Error::UserNotFoundId(_) => (StatusCode::NOT_FOUND, "User not found"),
                crate::types::Error::DatabaseError(_) => {
                    (StatusCode::INTERNAL_SERVER_ERROR, "Internal server error")
                }
                _ => unreachable!(),
            };
            Err((status, Json(error.into())))?
        }
    };
    save_media(post.id, &data, &content_type)
        .await
        .map_err(|_| {
            (
                StatusCode::INTERNAL_SERVER_ERROR,
                Json("Failed to write media".into()),
            )
        })?;
    Ok(Json(CreatePostResponse { post }))
}

#[derive(Serialize)]
struct GetPostResponse {
    post: Post,
}
pub async fn get_post(
    Extension(db): Extension<Database>,
    Path(id): Path<u32>,
) -> Result<impl IntoResponse, (StatusCode, Json<ErrorResponse>)> {
    match db.get_post_by_id(&id).await {
        Ok(post) => Ok((StatusCode::OK, Json(GetPostResponse { post }))),
        Err(err) => {
            println!("{:?}", err);
            let (status, msg) = match err {
                crate::types::Error::PostNotFound(_) => (StatusCode::NOT_FOUND, "Post not found"),
                crate::types::Error::DatabaseError(_) => {
                    (StatusCode::INTERNAL_SERVER_ERROR, "Internal server error")
                }
                _ => unreachable!(),
            };
            Err((
                status,
                Json(ErrorResponse {
                    message: msg.into(),
                }),
            ))
        }
    }
}
