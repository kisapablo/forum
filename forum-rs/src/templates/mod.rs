// templates/mod.rs
//! Sailfish templates module
//!
//! This module contains all the template structures for rendering HTML pages.
//! Sailfish compiles templates at build time for maximum performance.
//!
//! Template files are located in the templates/ directory and use the .stpl extension.

pub mod auth;
pub mod comments;
pub mod pages;
pub mod posts;
pub mod user;

// Re-export commonly used templates
pub use auth::*;
pub use comments::*;
pub use pages::*;
pub use posts::*;
pub use user::*;
