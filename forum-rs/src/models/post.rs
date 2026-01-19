// models/post.rs
//! Post model and related database operations
//!
//! This module handles all forum post operations including:
//! - Creating, reading, updating, and deleting posts
//! - Post pagination
//! - Searching and filtering posts
//! - Managing post attachments

use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// Represents a forum post
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct Post {
    pub id: i64,
    pub title: String,
    pub content: String,
    pub publication_date: DateTime<Utc>,
    pub author_id: i64,
}

/// Post with author information (from user_post_view)
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct PostWithAuthor {
    pub id: i64,
    pub title: String,
    pub content: String,
    pub publication_date: DateTime<Utc>,
    pub author_id: i64,
    pub author_name: String,
    pub author_icon_name: Option<String>,
}

/// Post with tags for display
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct PostWithTags {
    #[serde(flatten)]
    pub post: PostWithAuthor,
    pub tags: Vec<super::Tag>,
}

/// Pagination information
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Pagination {
    pub current: i64,
    pub pages_count: i64,
}

impl Default for Pagination {
    fn default() -> Self {
        Self {
            current: 0,
            pages_count: 1,
        }
    }
}

impl Post {
    /// Posts per page for pagination
    pub const PER_PAGE: i64 = 3;

    /// Find post by ID
    pub async fn find_by_id(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, Post>("SELECT * FROM post WHERE id = ?")
            .bind(post_id)
            .fetch_optional(pool)
            .await
    }

    /// Get post with author information
    pub async fn find_with_author(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<Option<PostWithAuthor>, sqlx::Error> {
        sqlx::query_as::<_, PostWithAuthor>(
            "SELECT * FROM user_post_view WHERE id = ?",
        )
        .bind(post_id)
        .fetch_optional(pool)
        .await
    }

    /// Create a new post
    pub async fn create(
        pool: &MySqlPool,
        title: &str,
        content: &str,
        author_id: i64,
    ) -> Result<i64, sqlx::Error> {
        let result = sqlx::query(
            "INSERT INTO post (title, content, author_id) VALUES (?, ?, ?)",
        )
        .bind(title)
        .bind(content)
        .bind(author_id)
        .execute(pool)
        .await?;

        Ok(result.last_insert_id() as i64)
    }

    /// Update post
    pub async fn update(
        pool: &MySqlPool,
        post_id: i64,
        title: &str,
        content: &str,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("UPDATE post SET title = ?, content = ? WHERE id = ?")
            .bind(title)
            .bind(content)
            .bind(post_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Delete post
    pub async fn delete(
        pool: &MySqlPool,
        post_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("DELETE FROM post WHERE id = ?")
            .bind(post_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Get all posts with pagination and optional filtering
    pub async fn get_all_paginated(
        pool: &MySqlPool,
        page: i64,
        author_id: Option<i64>,
        search: Option<&str>,
        tag_id: Option<i32>,
    ) -> Result<Vec<PostWithAuthor>, sqlx::Error> {
        let offset = (page - 1) * Self::PER_PAGE;

        let mut query = String::from("SELECT * FROM user_post_view WHERE 1=1");

        if author_id.is_some() {
            query.push_str(" AND author_id = ?");
        }
        if search.is_some() {
            query.push_str(" AND title LIKE ?");
        }
        if tag_id.is_some() {
            query.push_str(" AND id IN (SELECT post_id FROM m2m_tag_post WHERE tag_id = ?)");
        }

        query.push_str(" ORDER BY publication_date DESC LIMIT ? OFFSET ?");

        let mut q = sqlx::query_as::<_, PostWithAuthor>(&query);

        if let Some(aid) = author_id {
            q = q.bind(aid);
        }
        if let Some(s) = search {
            q = q.bind(format!("%{}%", s));
        }
        if let Some(tid) = tag_id {
            q = q.bind(tid);
        }

        q = q.bind(Self::PER_PAGE).bind(offset);

        q.fetch_all(pool).await
    }

    /// Count total posts (with optional filtering)
    pub async fn count_all(
        pool: &MySqlPool,
        author_id: Option<i64>,
        search: Option<&str>,
        tag_id: Option<i32>,
    ) -> Result<i64, sqlx::Error> {
        let mut query =
            String::from("SELECT COUNT(*) as count FROM post WHERE 1=1");

        if author_id.is_some() {
            query.push_str(" AND author_id = ?");
        }
        if search.is_some() {
            query.push_str(" AND title LIKE ?");
        }
        if tag_id.is_some() {
            query.push_str(" AND id IN (SELECT post_id FROM m2m_tag_post WHERE tag_id = ?)");
        }

        let mut q = sqlx::query_scalar::<_, i64>(&query);

        if let Some(aid) = author_id {
            q = q.bind(aid);
        }
        if let Some(s) = search {
            q = q.bind(format!("%{}%", s));
        }
        if let Some(tid) = tag_id {
            q = q.bind(tid);
        }

        q.fetch_one(pool).await
    }

    /// Calculate pagination
    pub async fn calculate_pagination(
        pool: &MySqlPool,
        page: i64,
        author_id: Option<i64>,
        search: Option<&str>,
        tag_id: Option<i32>,
    ) -> Result<Pagination, sqlx::Error> {
        let total = Self::count_all(pool, author_id, search, tag_id).await?;
        let pages_count = (total + Self::PER_PAGE - 1) / Self::PER_PAGE;

        Ok(Pagination {
            current: page,
            pages_count,
        })
    }
}
