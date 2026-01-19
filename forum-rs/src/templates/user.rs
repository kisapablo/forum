// templates/user.rs
//! User profile templates
//!
//! Templates for user profile and settings pages.

use crate::models::{SessionUser, User, UserIconView, UserInfo};
use sailfish::TemplateOnce;

/// Personal cabinet template
#[derive(TemplateOnce)]
#[template(path = "PersonalCabinet.stpl")]
pub struct PersonalCabinetTemplate {
    pub user: Option<SessionUser>,
    pub cabinet: Option<UserInfo>,
    pub icons: Option<UserIconView>,
}

/// User editor template
#[derive(TemplateOnce)]
#[template(path = "UserEditor.stpl")]
pub struct UserEditorTemplate {
    pub user: Option<SessionUser>,
    pub user_data: Option<User>,
    pub message: Option<String>,
}

/// Select avatar template
#[derive(TemplateOnce)]
#[template(path = "SelectAvatar.stpl")]
pub struct SelectAvatarTemplate {
    pub user: Option<SessionUser>,
    pub icons: Vec<UserIconView>,
}

/// Bug report template
#[derive(TemplateOnce)]
#[template(path = "report.stpl")]
pub struct ReportTemplate {
    pub user: Option<SessionUser>,
    pub message: Option<String>,
}
