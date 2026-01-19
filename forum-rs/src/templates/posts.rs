// templates/posts.rs
//! Post templates
//!
//! Templates for displaying and managing posts.

use crate::models::{
    CommentWithAttachments, Pagination, Post, PostAttachmentView, PostWithTags,
    SessionUser,
};
use sailfish::TemplateOnce;

/// Homepage/post listing template
#[derive(TemplateOnce)]
#[template(path = "index.stpl")]
pub struct IndexTemplate {
    pub user: Option<SessionUser>,
    pub posts: Vec<PostWithTags>,
    pub pagination: Pagination,
    pub search_term: Option<String>,
}

/// Single post page template
#[derive(TemplateOnce)]
#[template(path = "post.stpl")]
pub struct PostPageTemplate {
    pub user: Option<SessionUser>,
    pub post: PostWithTags,
    pub post_attachments: Vec<PostAttachmentView>,
    pub comments: Vec<CommentWithAttachments>,
}

/// Create post form template
#[derive(TemplateOnce)]
#[template(path = "CreateNewPosts.stpl")]
pub struct CreatePostTemplate {
    pub user: Option<SessionUser>,
    pub message: Option<String>,
}

/// Post editor template
#[derive(TemplateOnce)]
#[template(path = "PostEditor.stpl")]
pub struct PostEditorTemplate {
    pub user: Option<SessionUser>,
    pub post: Post,
    pub message: Option<String>,
}

/// Delete post confirmation template
#[derive(TemplateOnce)]
#[template(path = "DeletePost.stpl")]
pub struct DeletePostTemplate {
    pub user: Option<SessionUser>,
    pub post_id: i64,
}

/// Karma leaderboard template
#[derive(TemplateOnce)]
#[template(path = "LeaderKarma.stpl")]
pub struct LeaderKarmaTemplate {
    pub user: Option<SessionUser>,
    pub leaders: Vec<crate::models::UserInfo>,
}

/// 404 Not Found template
#[derive(TemplateOnce)]
#[template(path = "not-found.stpl")]
pub struct NotFoundTemplate {
    pub user: Option<SessionUser>,
}
