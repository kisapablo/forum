// utils/file_upload.rs
//! File upload utilities
//!
//! Handles saving uploaded files to disk with proper validation.

use bytes::Bytes;
use std::path::PathBuf;
use tokio::fs;
use tokio::io::AsyncWriteExt;

/// Save an uploaded file to the static/images directory
///
/// # Arguments
/// * `data` - File content as bytes
/// * `filename` - Original filename from upload
///
/// # Returns
/// * `Ok(String)` - Relative path to saved file (e.g., "public/images/filename.jpg")
/// * `Err` - If file save fails
pub async fn save_uploaded_file(
    data: &Bytes,
    filename: &str,
) -> Result<String, std::io::Error> {
    // Create uploads directory if it doesn't exist
    let upload_dir = PathBuf::from("forum-rs/static/images");
    fs::create_dir_all(&upload_dir).await?;

    // Sanitize filename to prevent directory traversal
    let safe_filename = sanitize_filename(filename);

    // Generate unique filename to avoid collisions
    let timestamp = chrono::Utc::now().timestamp();
    let unique_filename = format!("{}_{}", timestamp, safe_filename);

    // Full path for saving
    let file_path = upload_dir.join(&unique_filename);

    // Write file
    let mut file = fs::File::create(&file_path).await?;
    file.write_all(data).await?;
    file.flush().await?;

    // Return relative path for database storage
    Ok(format!("public/images/{}", unique_filename))
}

/// Sanitize filename to prevent security issues
fn sanitize_filename(filename: &str) -> String {
    // Remove path separators and only keep alphanumeric, dots, dashes, underscores
    filename
        .chars()
        .filter(|c| c.is_alphanumeric() || *c == '.' || *c == '-' || *c == '_')
        .collect::<String>()
        .chars()
        .take(255) // Limit filename length
        .collect()
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_sanitize_filename() {
        assert_eq!(sanitize_filename("test.jpg"), "test.jpg");
        assert_eq!(sanitize_filename("../../../etc/passwd"), "etcpasswd");
        assert_eq!(
            sanitize_filename("file with spaces.png"),
            "filewithspaces.png"
        );
        assert_eq!(sanitize_filename("test@#$%.jpg"), "test.jpg");
    }
}
