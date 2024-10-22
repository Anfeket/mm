pub mod handlers;
pub mod routes;

use tokio::fs::write;
use std::path::Path;

async fn save_media(post_id: u32, data: &[u8], content_type: &str) -> Result<String, std::io::Error> {
    let extension = match content_type {
        "image/png" => "png",
        "image/jpeg" => "jpg",
        "video/mp4" => "mp4",
        _ => "bin",
    };

    let file_name = format!("uploads/{}.{}", post_id, extension);
    let file_path = Path::new(&file_name);

    write(file_path, data).await?;

    Ok(file_name)
}
