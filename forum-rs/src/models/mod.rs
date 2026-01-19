// models/mod.rs
//! Database models module
//!
//! This module contains all the database models that map to our SQL tables.
//! Each model corresponds to a table in the database schema.

pub mod attachment;
pub mod comment;
pub mod post;
pub mod role;
pub mod tag;
pub mod user;

pub use attachment::*;
pub use comment::*;
pub use post::*;
pub use role::*;
pub use tag::*;
pub use user::*;
