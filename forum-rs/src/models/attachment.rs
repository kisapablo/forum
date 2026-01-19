// models/attachment.rs
//! Attachment model for file uploads
//!
//! This module handles file attachments for posts, comments, and user icons.
//! It includes database views for easy retrieval of attachment information.

use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// Generic attachment record
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct Attachment {
    pub id: i64,
    pub name: String,
}

/// Post attachment view
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct PostAttachmentView {
    pub id: i64,
    pub attachment_name: String,
    pub post_id: i64,
    pub post_title: String,
    pub author_id: i64,
    pub author_name: String,
}

/// Comment attachment view
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct CommentAttachmentView {
    pub id: i64,
    pub attachment_name: String,
    pub comment_id: i64,
    pub author_id: i64,
    pub author_name: String,
    pub post_id: i64,
}

/// User icon view
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct UserIconView {
    pub id: i64,
    pub icon_name: String,
    pub is_default: bool,
    pub user_id: i64,
}

impl Attachment {
    /// Create a new attachment
    pub async fn create(
        pool: &MySqlPool,
        name: &str,
    ) -> Result<i64, sqlx::Error> {
        let result = sqlx::query("INSERT INTO attachment (name) VALUES (?)")
            .bind(name)
            .execute(pool)
            .await?;
        Ok(result.last_insert_id() as i64)
    }

    /// Add post attachment using stored procedure
    pub async fn add_post_attachment(
        pool: &MySqlPool,
        name: &str,
        post_id: i64,
    ) -> Result<Option<i64>, sqlx::Error> {
        let result: Option<(Option<i64>,)> = sqlx::query_as(
            "CALL AddPostAttachment(?, ?, @attachment_id); SELECT @attachment_id"
        )
        .bind(name)
        .bind(post_id)
        .fetch_optional(pool)
        .await?;

        Ok(result.and_then(|(id,)| id))
    }

    /// Add comment attachment using stored procedure
    pub async fn add_comment_attachment(
        pool: &MySqlPool,
        name: &str,
        comment_id: i64,
    ) -> Result<Option<i64>, sqlx::Error> {
        let result: Option<(Option<i64>,)> = sqlx::query_as(
            "CALL AddCommentAttachment(?, ?, @attachment_id); SELECT @attachment_id"
        )
        .bind(name)
        .bind(comment_id)
        .fetch_optional(pool)
        .await?;

        Ok(result.and_then(|(id,)| id))
    }

    /// Add user icon using stored procedure
    pub async fn add_user_icon(
        pool: &MySqlPool,
        name: &str,
        user_id: i64,
        is_default: bool,
    ) -> Result<Option<i64>, sqlx::Error> {
        let result: Option<(Option<i64>,)> = sqlx::query_as(
            "CALL AddUserIcon(?, ?, ?, @attachment_id); SELECT @attachment_id",
        )
        .bind(name)
        .bind(user_id)
        .bind(is_default)
        .fetch_optional(pool)
        .await?;

        Ok(result.and_then(|(id,)| id))
    }

    /// Get post attachments
    pub async fn get_post_attachments(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<Vec<PostAttachmentView>, sqlx::Error> {
        sqlx::query_as::<_, PostAttachmentView>(
            "SELECT * FROM post_attachment_view WHERE post_id = ?",
        )
        .bind(post_id)
        .fetch_all(pool)
        .await
    }

    /// Get comment attachments
    pub async fn get_comment_attachments(
        pool: &MySqlPool,
        comment_id: i64,
    ) -> Result<Vec<CommentAttachmentView>, sqlx::Error> {
        sqlx::query_as::<_, CommentAttachmentView>(
            "SELECT * FROM comment_attachment_view WHERE comment_id = ?",
        )
        .bind(comment_id)
        .fetch_all(pool)
        .await
    }

    /// Get user icon
    pub async fn get_user_icon(
        pool: &MySqlPool,
        user_id: i64,
    ) -> Result<Option<UserIconView>, sqlx::Error> {
        sqlx::query_as::<_, UserIconView>(
            "SELECT * FROM user_icon_view WHERE user_id = ? ORDER BY is_default DESC LIMIT 1"
        )
        .bind(user_id)
        .fetch_optional(pool)
        .await
    }

    /// Get all default icons
    pub async fn get_default_icons(
        pool: &MySqlPool,
    ) -> Result<Vec<UserIconView>, sqlx::Error> {
        sqlx::query_as::<_, UserIconView>(
            "SELECT * FROM user_icon_view WHERE is_default = TRUE",
        )
        .fetch_all(pool)
        .await
    }
}
