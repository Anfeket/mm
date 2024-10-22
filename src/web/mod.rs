pub mod handlers;
pub mod routes;

use std::path::Path;
use tokio::fs::{read, write};

async fn save_media(
    post_id: u32,
    data: &[u8],
    extension: &str,
) -> Result<String, std::io::Error> {
    let file_name = format!("uploads/{}.{}", post_id, extension);
    let file_path = Path::new(&file_name);

    write(file_path, data).await?;

    Ok(file_name)
}

async fn get_media(id: &u32, extension: &str) -> Result<Option<Vec<u8>>, std::io::Error> {
    match read(format!("uploads/{}.{}", id, extension)).await {
        Ok(data) => Ok(Some(data)),
        Err(err) => {
            if err.kind() == std::io::ErrorKind::NotFound {
                Ok(None)
            } else {
                Err(err)
            }
        }
    }
}
