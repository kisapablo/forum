// models/comment.rs
//! Comment model and related database operations
//!
//! This module handles all comment operations including:
//! - Creating, reading, updating, and deleting comments
//! - Managing comment attachments
//! - Retrieving comments with author information

use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// Represents a comment on a post
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct Comment {
    pub id: i64,
    pub content: String,
    pub author_id: i64,
    pub post_id: i64,
    pub publication_date: DateTime<Utc>,
}

/// Comment with author information (from user_comment_view)
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct CommentWithAuthor {
    pub id: i64,
    pub content: String,
    pub publication_date: DateTime<Utc>,
    pub post_id: i64,
    pub author_id: i64,
    pub author_name: String,
    pub author_icon_name: Option<String>,
}

/// Comment with attachments
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct CommentWithAttachments {
    #[serde(flatten)]
    pub comment: CommentWithAuthor,
    pub attachments: Vec<super::CommentAttachmentView>,
}

impl Comment {
    /// Find comment by ID
    pub async fn find_by_id(
        pool: &MySqlPool,
        comment_id: i64,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, Comment>("SELECT * FROM comment WHERE id = ?")
            .bind(comment_id)
            .fetch_optional(pool)
            .await
    }

    /// Get comment with author information
    pub async fn find_with_author(
        pool: &MySqlPool,
        comment_id: i64,
    ) -> Result<Option<CommentWithAuthor>, sqlx::Error> {
        sqlx::query_as::<_, CommentWithAuthor>(
            "SELECT * FROM user_comment_view WHERE id = ?",
        )
        .bind(comment_id)
        .fetch_optional(pool)
        .await
    }

    /// Create a new comment
    pub async fn create(
        pool: &MySqlPool,
        content: &str,
        author_id: i64,
        post_id: i64,
    ) -> Result<i64, sqlx::Error> {
        let result = sqlx::query(
            "INSERT INTO comment (content, author_id, post_id) VALUES (?, ?, ?)"
        )
        .bind(content)
        .bind(author_id)
        .bind(post_id)
        .execute(pool)
        .await?;

        Ok(result.last_insert_id() as i64)
    }

    /// Update comment
    pub async fn update(
        pool: &MySqlPool,
        comment_id: i64,
        content: &str,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("UPDATE comment SET content = ? WHERE id = ?")
            .bind(content)
            .bind(comment_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Delete comment
    pub async fn delete(
        pool: &MySqlPool,
        comment_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("DELETE FROM comment WHERE id = ?")
            .bind(comment_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Get all comments for a post with author information
    pub async fn get_by_post(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<Vec<CommentWithAuthor>, sqlx::Error> {
        sqlx::query_as::<_, CommentWithAuthor>(
            "SELECT * FROM user_comment_view WHERE post_id = ? ORDER BY publication_date ASC"
        )
        .bind(post_id)
        .fetch_all(pool)
        .await
    }

    /// Delete all comments for a post
    pub async fn delete_by_post(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("DELETE FROM comment WHERE post_id = ?")
            .bind(post_id)
            .execute(pool)
            .await?;
        Ok(())
    }
}
