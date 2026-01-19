// models/user.rs
//! User model and related database operations
//!
//! This module handles all user-related database operations including:
//! - User authentication (login/registration)
//! - User profile management
//! - Password hashing and verification

use chrono::{DateTime, Utc};
use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// Represents a user in the system
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct User {
    pub id: i64,
    pub name: String,
    pub password_hash: String,
    pub password_salt: String,
    pub role_id: i16,
    pub icon_id: Option<i64>,
    pub registration_date: DateTime<Utc>,
    pub last_visit_date: Option<DateTime<Utc>>,
    pub moto: String,
}

/// User data for display (without sensitive information)
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct UserInfo {
    pub user_id: i64,
    pub role_id: i16,
    pub en_name: String, // role english name
    pub ru_name: String, // role russian name
    pub registration_date: DateTime<Utc>,
    pub last_visit_date: Option<DateTime<Utc>>,
    pub moto: String,
    pub total: i64, // total posts count
}

/// Session user data (stored in session)
#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct SessionUser {
    pub id: i64,
    pub name: String,
    pub icon_name: String,
}

impl User {
    /// Find user by ID
    pub async fn find_by_id(
        pool: &MySqlPool,
        user_id: i64,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, User>("SELECT * FROM user WHERE id = ?")
            .bind(user_id)
            .fetch_optional(pool)
            .await
    }

    /// Find user by username
    pub async fn find_by_name(
        pool: &MySqlPool,
        username: &str,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, User>("SELECT * FROM user WHERE name = ?")
            .bind(username)
            .fetch_optional(pool)
            .await
    }

    /// Create a new user
    pub async fn create(
        pool: &MySqlPool,
        username: &str,
        password_hash: &str,
        password_salt: &str,
        role_id: i16,
    ) -> Result<i64, sqlx::Error> {
        let result = sqlx::query(
            "INSERT INTO user (name, password_hash, password_salt, role_id, registration_date) 
             VALUES (?, ?, ?, ?, NOW())"
        )
        .bind(username)
        .bind(password_hash)
        .bind(password_salt)
        .bind(role_id)
        .execute(pool)
        .await?;

        Ok(result.last_insert_id() as i64)
    }

    /// Update user's last visit date
    pub async fn update_last_visit(
        pool: &MySqlPool,
        user_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("UPDATE user SET last_visit_date = NOW() WHERE id = ?")
            .bind(user_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Update user profile (name, moto, password)
    pub async fn update_profile(
        pool: &MySqlPool,
        user_id: i64,
        username: Option<&str>,
        password_hash: Option<&str>,
        password_salt: Option<&str>,
        moto: Option<&str>,
    ) -> Result<(), sqlx::Error> {
        let mut query = String::from("UPDATE user SET ");
        let mut updates = Vec::new();

        if username.is_some() {
            updates.push("name = ?");
        }
        if password_hash.is_some() && password_salt.is_some() {
            updates.push("password_hash = ?");
            updates.push("password_salt = ?");
        }
        if moto.is_some() {
            updates.push("moto = ?");
        }

        if updates.is_empty() {
            return Ok(());
        }

        query.push_str(&updates.join(", "));
        query.push_str(" WHERE id = ?");

        let mut q = sqlx::query(&query);

        if let Some(name) = username {
            q = q.bind(name);
        }
        if let Some(hash) = password_hash {
            q = q.bind(hash);
            if let Some(salt) = password_salt {
                q = q.bind(salt);
            }
        }
        if let Some(m) = moto {
            q = q.bind(m);
        }
        q = q.bind(user_id);

        q.execute(pool).await?;
        Ok(())
    }

    /// Update user's icon
    pub async fn update_icon(
        pool: &MySqlPool,
        user_id: i64,
        icon_id: i64,
    ) -> Result<(), sqlx::Error> {
        sqlx::query("UPDATE user SET icon_id = ? WHERE id = ?")
            .bind(icon_id)
            .bind(user_id)
            .execute(pool)
            .await?;
        Ok(())
    }

    /// Get user info (with role and stats) from user_info_view
    pub async fn get_user_info(
        pool: &MySqlPool,
        user_id: i64,
    ) -> Result<Option<UserInfo>, sqlx::Error> {
        sqlx::query_as::<_, UserInfo>(
            "SELECT * FROM user_info_view WHERE user_id = ?",
        )
        .bind(user_id)
        .fetch_optional(pool)
        .await
    }

    /// Get karma leaders (users ordered by post count)
    pub async fn get_karma_leaders(
        pool: &MySqlPool,
    ) -> Result<Vec<UserInfo>, sqlx::Error> {
        sqlx::query_as::<_, UserInfo>(
            "SELECT * FROM user_info_view ORDER BY total DESC",
        )
        .fetch_all(pool)
        .await
    }

    /// Verify password
    pub fn verify_password(&self, password: &str) -> bool {
        bcrypt::verify(password, &self.password_hash).unwrap_or(false)
    }

    /// Hash a password with bcrypt
    pub fn hash_password(
        password: &str,
    ) -> Result<String, bcrypt::BcryptError> {
        bcrypt::hash(password, bcrypt::DEFAULT_COST)
    }

    /// Get user's icon name or default
    pub async fn get_icon_name(
        pool: &MySqlPool,
        user: &User,
    ) -> Result<String, sqlx::Error> {
        if let Some(icon_id) = user.icon_id {
            // Try to get icon from user_icon_view
            let result: Option<(String,)> = sqlx::query_as(
                "SELECT icon_name FROM user_icon_view WHERE id = ? AND user_id = ?",
            )
            .bind(icon_id)
            .bind(user.id)
            .fetch_optional(pool)
            .await?;

            if let Some((icon_name,)) = result {
                return Ok(icon_name);
            }
        }

        // Default icon
        Ok("/public/images/default_ico/default-avatar.png".to_string())
    }
}
