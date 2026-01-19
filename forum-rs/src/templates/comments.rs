// templates/comments.rs
//! Comment templates
//!
//! Templates for managing comments.

use crate::models::{Comment, SessionUser};
use sailfish::TemplateOnce;

/// Delete comment confirmation template
#[derive(TemplateOnce)]
#[template(path = "DeleteComments.stpl")]
pub struct DeleteCommentTemplate {
    pub user: Option<SessionUser>,
    pub post_id: i64,
    pub comment_id: i64,
}

/// Comment editor template
#[derive(TemplateOnce)]
#[template(path = "CommentEditor.stpl")]
pub struct CommentEditorTemplate {
    pub user: Option<SessionUser>,
    pub post_id: i64,
    pub comment: Comment,
    pub message: Option<String>,
}
