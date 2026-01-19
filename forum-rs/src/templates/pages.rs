// templates/pages.rs
//! Static page templates
//!
//! Templates for about page, admin panel, etc.

use crate::models::SessionUser;
use sailfish::TemplateOnce;

/// About page template
#[derive(TemplateOnce)]
#[template(path = "about.stpl")]
pub struct AboutTemplate {
    pub user: Option<SessionUser>,
}

/// Admin panel template
#[derive(TemplateOnce)]
#[template(path = "admin.stpl")]
pub struct AdminTemplate {
    pub user: Option<SessionUser>,
}
