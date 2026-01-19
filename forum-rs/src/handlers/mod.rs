// handlers/mod.rs
//! HTTP request handlers module
//!
//! This module contains all the route handlers organized by feature:
//! - auth: User authentication (login, register, logout)
//! - posts: Post management (CRUD operations)
//! - comments: Comment management
//! - user: User profile and settings
//! - pages: Static pages (about, admin, etc.)

pub mod auth;
pub mod comments;
pub mod pages;
pub mod posts;
pub mod user;

pub use auth::*;
pub use comments::*;
pub use pages::*;
pub use posts::*;
pub use user::*;
