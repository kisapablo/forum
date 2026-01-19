// lib.rs
//! Forum-RS - A forum web application written in Rust
//!
//! This is a rewrite of a PHP forum application using modern Rust technologies:
//! - Axum web framework for routing and HTTP handling
//! - SQLx for type-safe database queries
//! - Sailfish for fast template rendering
//! - Bcrypt for secure password hashing
//! - Tower sessions for user session management
//!
//! # Architecture
//!
//! The application follows a clean architecture pattern:
//! - `models/` - Database models and data access layer
//! - `handlers/` - HTTP request handlers (controllers)
//! - `templates/` - Sailfish HTML templates
//! - `middleware/` - Authentication and other middleware
//! - `utils/` - Utility functions (file uploads, etc.)
//!
//! # Database
//!
//! Uses MySQL with the following main tables:
//! - `user` - User accounts with authentication
//! - `post` - Forum posts
//! - `comment` - Comments on posts
//! - `attachment` - File attachments
//! - `tag` - Post categorization tags
//! - `role` - User roles (Admin/User)
//!
//! # Features
//!
//! - User registration and authentication
//! - Create, read, update, delete posts
//! - Comment on posts
//! - File attachments for posts and comments
//! - User profiles with avatars
//! - Tag-based categorization
//! - Search and filtering
//! - Pagination
//! - Karma/reputation system
//! - Admin panel
//!
//! # For Junior Developers
//!
//! If you're new to Rust or this codebase, start by reading:
//! 1. The README.md for setup instructions
//! 2. `models/mod.rs` to understand the data structures
//! 3. `handlers/mod.rs` to see how HTTP requests are handled
//! 4. Pick a simple feature like viewing a post and trace the code from handler -> model -> database
//!
//! Key Rust concepts used in this project:
//! - `async/await` - All database and I/O operations are asynchronous
//! - `Result<T, E>` - Error handling without exceptions
//! - `Option<T>` - Handling nullable values safely
//! - Ownership and borrowing - Memory safety without garbage collection
//! - Pattern matching - Used extensively for error handling and control flow

pub mod handlers;
pub mod middleware;
pub mod models;
pub mod templates;
pub mod utils;

use sqlx::MySqlPool;

/// Application state shared across all handlers
///
/// This struct is cloned for each request (cheap because of Arc)
/// and contains the database connection pool.
#[derive(Clone)]
pub struct AppState {
    /// Database connection pool
    pub db: MySqlPool,
}

impl AppState {
    /// Create new application state with database connection
    pub fn new(db: MySqlPool) -> Self {
        Self { db }
    }
}

/// Database connection URL from environment
pub fn database_url() -> String {
    std::env::var("DATABASE_URL").unwrap_or_else(|_| {
        "mysql://quasi:root@localhost/petos_forum_db".to_string()
    })
}

/// Create database connection pool
///
/// # Errors
/// Returns error if connection fails
pub async fn create_db_pool() -> Result<MySqlPool, sqlx::Error> {
    let database_url = database_url();
    MySqlPool::connect(&database_url).await
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_database_url() {
        let url = database_url();
        assert!(url.starts_with("mysql://"));
    }
}
