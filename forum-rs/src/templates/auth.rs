// templates/auth.rs
//! Authentication templates
//!
//! Templates for login and registration pages.

use crate::models::SessionUser;
use sailfish::TemplateOnce;

/// Login page template
#[derive(TemplateOnce)]
#[template(path = "user-login.stpl")]
pub struct LoginTemplate {
    pub user: Option<SessionUser>,
    pub message: Option<String>,
}

/// Registration page template
#[derive(TemplateOnce)]
#[template(path = "user-registration.stpl")]
pub struct RegisterTemplate {
    pub user: Option<SessionUser>,
    pub message: Option<String>,
}
