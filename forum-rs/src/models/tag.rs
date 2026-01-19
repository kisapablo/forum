// models/tag.rs
//! Tag model for categorizing posts
//!
//! Tags allow posts to be categorized and searched by topic.

use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// Represents a tag
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct Tag {
    pub id: i32,
    pub name: String,
}

/// Post tag view (from post_tag_view)
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct PostTagView {
    pub post_id: i64,
    pub tag_id: i32,
    pub tag_name: String,
}

impl Tag {
    /// Find tag by ID
    pub async fn find_by_id(
        pool: &MySqlPool,
        tag_id: i32,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, Tag>("SELECT * FROM tag WHERE id = ?")
            .bind(tag_id)
            .fetch_optional(pool)
            .await
    }

    /// Find or create tag by name
    pub async fn find_or_create(
        pool: &MySqlPool,
        name: &str,
    ) -> Result<i32, sqlx::Error> {
        // Try to find existing tag
        if let Some(tag) =
            sqlx::query_as::<_, Tag>("SELECT * FROM tag WHERE name = ?")
                .bind(name)
                .fetch_optional(pool)
                .await?
        {
            return Ok(tag.id);
        }

        // Create new tag
        let result = sqlx::query("INSERT INTO tag (name) VALUES (?)")
            .bind(name)
            .execute(pool)
            .await?;

        Ok(result.last_insert_id() as i32)
    }

    /// Get all tags
    pub async fn get_all(pool: &MySqlPool) -> Result<Vec<Self>, sqlx::Error> {
        sqlx::query_as::<_, Tag>("SELECT * FROM tag ORDER BY name")
            .fetch_all(pool)
            .await
    }

    /// Get tags for a post
    pub async fn get_by_post(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<Vec<Self>, sqlx::Error> {
        sqlx::query_as::<_, Tag>(
            "SELECT t.* FROM tag t 
             JOIN m2m_tag_post m ON t.id = m.tag_id 
             WHERE m.post_id = ?",
        )
        .bind(post_id)
        .fetch_all(pool)
        .await
    }

    /// Add tag to post
    pub async fn add_to_post(
        pool: &MySqlPool,
        tag_id: i32,
        post_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query(
            "INSERT IGNORE INTO m2m_tag_post (tag_id, post_id) VALUES (?, ?)",
        )
        .bind(tag_id)
        .bind(post_id)
        .execute(pool)
        .await?;
        Ok(())
    }

    /// Remove tag from post
    pub async fn remove_from_post(
        pool: &MySqlPool,
        tag_id: i32,
        post_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query(
            "DELETE FROM m2m_tag_post WHERE tag_id = ? AND post_id = ?",
        )
        .bind(tag_id)
        .bind(post_id)
        .execute(pool)
        .await?;
        Ok(())
    }
}
