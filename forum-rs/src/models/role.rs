// models/role.rs
//! Role model for user permissions
//!
//! Roles define what users can do in the system:
//! - Admin (id=1): Full system access
//! - User (id=2): Regular user permissions

use serde::{Deserialize, Serialize};
use sqlx::{FromRow, MySqlPool};

/// User role in the system
#[derive(Debug, Clone, Serialize, Deserialize, FromRow)]
pub struct Role {
    pub id: i16,
    pub en_name: String,
    pub ru_name: String,
}

impl Role {
    /// Role ID for Admin
    pub const ADMIN: i16 = 1;

    /// Role ID for regular User
    pub const USER: i16 = 2;

    /// Find role by ID
    pub async fn find_by_id(
        pool: &MySqlPool,
        role_id: i16,
    ) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, Role>("SELECT * FROM role WHERE id = ?")
            .bind(role_id)
            .fetch_optional(pool)
            .await
    }

    /// Get all roles
    pub async fn get_all(pool: &MySqlPool) -> Result<Vec<Self>, sqlx::Error> {
        sqlx::query_as::<_, Role>("SELECT * FROM role")
            .fetch_all(pool)
            .await
    }
}
