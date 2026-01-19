// middleware/auth.rs
//! Authentication middleware
//!
//! Provides middleware for protecting routes that require authentication.

use axum::response::{IntoResponse, Redirect, Response};
use tower_sessions::Session;

use crate::models::SessionUser;

/// Middleware to require authentication
///
/// This can be used to protect routes that require a logged-in user.
/// Returns a redirect to /user/login if user is not authenticated.
pub async fn require_auth(session: Session) -> Result<(), Response> {
    match session.get::<SessionUser>("user").await {
        Ok(Some(_)) => Ok(()),
        _ => Err(Redirect::to("/user/login").into_response()),
    }
}

/// Extract optional user from session
///
/// This is a helper function to get the current user if logged in.
pub async fn get_session_user(session: &Session) -> Option<SessionUser> {
    session.get::<SessionUser>("user").await.ok().flatten()
}
