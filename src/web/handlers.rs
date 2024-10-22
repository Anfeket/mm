use crate::types::Post;
use axum::{
    extract::{Multipart, Path},
    http::{
        header::{CONTENT_DISPOSITION, CONTENT_TYPE},
        HeaderMap, HeaderValue, StatusCode,
    },
    response::IntoResponse,
    Extension, Json,
};
use serde::{Deserialize, Serialize};

use crate::{
    db::Database,
    types::{PostType, User},
};

use super::{get_media, save_media};

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
fn internal_server_error() -> (StatusCode, Json<ErrorResponse>) {
    err_res(StatusCode::INTERNAL_SERVER_ERROR, "Internal server error")
}
fn err_res(status_code: StatusCode, msg: &str) -> (StatusCode, Json<ErrorResponse>) {
    (
        status_code,
        Json(ErrorResponse {
            message: msg.into(),
        }),
    )
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
                    err_res(StatusCode::NOT_FOUND, "User not found")
                }
                crate::types::Error::AuthFailedIncorrectPassword(_) => {
                    err_res(StatusCode::UNAUTHORIZED, "Incorrect password")
                }
                crate::types::Error::DatabaseError(_) => internal_server_error(),
                _ => unreachable!(),
            };
            Err((status, msg))
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
    let mut post_type = None;
    let mut mime_type = String::new();
    let mut author_id = None;
    let mut description = None;
    while let Ok(Some(field)) = multipart.next_field().await {
        if let Some(name) = field.name() {
            match name {
                "media" => {
                    let content_type = field
                        .content_type()
                        .ok_or_else(|| err_res(StatusCode::BAD_REQUEST, "Missing content type"))?
                        .to_string();
                    post_type = match content_type.as_str() {
                        "image/png" | "image/jpeg" => Some(PostType::Image),
                        "image/mp4" => Some(PostType::Video),
                        _ => Some(PostType::Other),
                    };
                    mime_type = content_type;
                    data = field.bytes().await.map_err(|e| {
                        err_res(
                            StatusCode::INTERNAL_SERVER_ERROR,
                            &format!("Failed to read data: {:?}", e),
                        )
                    })?;
                }
                "author_id" => {
                    let data = field.text().await.map_err(|e| {
                        err_res(
                            StatusCode::INTERNAL_SERVER_ERROR,
                            &format!("Failed to read data: {:?}", e),
                        )
                    })?;
                    let id = data.parse().map_err(|e| {
                        err_res(
                            StatusCode::BAD_REQUEST,
                            &format!("Bad author id format: {}", e),
                        )
                    })?;
                    author_id = Some(id)
                }
                "description" => {
                    let data = field.text().await.map_err(|e| {
                        err_res(
                            StatusCode::INTERNAL_SERVER_ERROR,
                            &format!("Failed to read data: {:?}", e),
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
            author_id.ok_or_else(|| err_res(StatusCode::BAD_REQUEST, "Missing author_id"))?;
        let description =
            description.ok_or_else(|| err_res(StatusCode::BAD_REQUEST, "Missing description"))?;
        let post_type =
            post_type.ok_or_else(|| err_res(StatusCode::BAD_REQUEST, "Missing post_type"))?;
        (author_id, description, post_type)
    };
    let post = match db
        .create_post(
            &author_id,
            &description,
            &post_type,
            &(data.len() as u64),
            &mime_type,
            None,
        )
        .await
    {
        Ok(post) => post,
        Err(err) => {
            let (status, msg) = match err {
                crate::types::Error::UserNotFoundId(_) => {
                    err_res(StatusCode::NOT_FOUND, "User not found")
                }
                crate::types::Error::DatabaseError(_) => internal_server_error(),
                _ => unreachable!(),
            };
            Err((status, msg))?
        }
    };
    let extension = mime_type.split_once('/').ok_or_else(internal_server_error)?.1;
    save_media(post.id, &data, extension)
        .await
        .map_err(|_| err_res(StatusCode::INTERNAL_SERVER_ERROR, "Failed to write media"))?;
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
            let (status, msg) = match err {
                crate::types::Error::PostNotFound(_) => {
                    err_res(StatusCode::NOT_FOUND, "Post not found")
                }
                crate::types::Error::DatabaseError(_) => internal_server_error(),
                _ => unreachable!(),
            };
            Err((status, msg))
        }
    }
}

pub async fn get_post_media(
    Extension(db): Extension<Database>,
    Path(id): Path<u32>,
) -> Result<impl IntoResponse, (StatusCode, Json<ErrorResponse>)> {
    let mime_type = db.get_post_mime_type(&id).await.map_err(|e| match e {
        crate::types::Error::PostNotFound(_) => err_res(StatusCode::NOT_FOUND, "Post not found"),
        crate::types::Error::DatabaseError(_) => internal_server_error(),
        _ => unreachable!(),
    })?;
    let extension = mime_type
        .split_once('/')
        .ok_or_else(internal_server_error)?
        .1;
    let data = get_media(&id, extension)
        .await
        .map_err(|_| err_res(StatusCode::INTERNAL_SERVER_ERROR, "Failed to read file"))?
        .ok_or_else(|| err_res(StatusCode::NOT_FOUND, "File not found"))?;
    let mut headers = HeaderMap::new();
    headers.insert(
        CONTENT_TYPE,
        HeaderValue::from_str(&mime_type).map_err(|_| internal_server_error())?,
    );
    headers.append(
        CONTENT_DISPOSITION,
        HeaderValue::from_str("inline").map_err(|_| internal_server_error())?,
    );
    Ok((StatusCode::OK, headers, data))
}
