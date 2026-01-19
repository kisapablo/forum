//! Comment handlers
//!
//! Handles all comment-related operations including creating, editing, and deleting comments.
use sailfish::TemplateOnce;

use axum::{
    extract::{Multipart, Path, State},
    response::{Html, IntoResponse, Redirect},
    Form,
};
use serde::Deserialize;
use tower_sessions::Session;

use crate::{
    models::{Attachment, Comment, SessionUser},
    templates::comments::*,
    utils::file_upload::save_uploaded_file,
    AppState,
};

/// Form data for creating a comment
#[derive(Debug, Deserialize)]
pub struct CreateCommentForm {
    pub content: String,
}

/// Create comment
/// POST /posts/{post_id}/comments
pub async fn create_comment(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
    mut multipart: Multipart,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    let mut content = String::new();
    let mut attachment_path: Option<String> = None;

    // Parse multipart form
    while let Ok(Some(field)) = multipart.next_field().await {
        let name = field.name().unwrap_or("").to_string();

        match name.as_str() {
            "content" => {
                content = field.text().await.unwrap_or_default();
            }
            "avatar" => {
                if let Some(filename) = field.file_name() {
                    let filename = filename.to_string();
                    let data = field.bytes().await.unwrap_or_default();
                    if !data.is_empty() {
                        attachment_path =
                            save_uploaded_file(&data, &filename).await.ok();
                    }
                }
            }
            _ => {}
        }
    }

    if content.is_empty() {
        return Redirect::to(&format!("/posts/{}", post_id));
    }

    // Create comment
    let comment_id =
        match Comment::create(&state.db, &content, user.id, post_id).await {
            Ok(id) => id,
            Err(_) => return Redirect::to(&format!("/posts/{}", post_id)),
        };

    // Add attachment if uploaded
    if let Some(path) = attachment_path {
        let _ =
            Attachment::add_comment_attachment(&state.db, &path, comment_id)
                .await;
    }

    Redirect::to(&format!("/posts/{}", post_id))
}

/// Show comment deletion confirmation
/// GET /posts/delete/{post_id}/comments/{comment_id}
pub async fn show_delete_comment(
    session: Session,
    State(state): State<AppState>,
    Path((post_id, comment_id)): Path<(i64, i64)>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let comment = match Comment::find_by_id(&state.db, comment_id).await {
        Ok(Some(c)) => c,
        _ => {
            return Redirect::to(&format!("/posts/{}", post_id)).into_response()
        }
    };

    // Check ownership
    if comment.author_id != user.id {
        return Redirect::to(&format!("/posts/{}", post_id)).into_response();
    }

    let template = DeleteCommentTemplate {
        user: Some(user),
        post_id,
        comment_id,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Delete comment
/// POST /posts/delete/{post_id}/comments/{comment_id}/initial
pub async fn delete_comment(
    session: Session,
    State(state): State<AppState>,
    Path((post_id, comment_id)): Path<(i64, i64)>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    let comment = match Comment::find_by_id(&state.db, comment_id).await {
        Ok(Some(c)) => c,
        _ => return Redirect::to(&format!("/posts/{}", post_id)),
    };

    // Check ownership
    if comment.author_id != user.id {
        return Redirect::to(&format!("/posts/{}", post_id));
    }

    let _ = Comment::delete(&state.db, comment_id).await;

    Redirect::to(&format!("/posts/{}", post_id))
}

/// Show comment editor
/// GET /user/posts/CommentEditor/{post_id}/{comment_id}
pub async fn show_comment_editor(
    session: Session,
    State(state): State<AppState>,
    Path((post_id, comment_id)): Path<(i64, i64)>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let comment = match Comment::find_by_id(&state.db, comment_id).await {
        Ok(Some(c)) => c,
        _ => {
            return Redirect::to(&format!("/posts/{}", post_id)).into_response()
        }
    };

    // Check ownership
    if comment.author_id != user.id {
        return Redirect::to(&format!("/posts/{}", post_id)).into_response();
    }

    let template = CommentEditorTemplate {
        user: Some(user),
        post_id,
        comment,
        message: None,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Update comment
/// POST /user/posts/CommentEditor/{post_id}/{comment_id}
pub async fn update_comment(
    session: Session,
    State(state): State<AppState>,
    Path((post_id, comment_id)): Path<(i64, i64)>,
    Form(form): Form<CreateCommentForm>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    let comment = match Comment::find_by_id(&state.db, comment_id).await {
        Ok(Some(c)) => c,
        _ => return Redirect::to(&format!("/posts/{}", post_id)),
    };

    // Check ownership
    if comment.author_id != user.id {
        return Redirect::to(&format!("/posts/{}", post_id));
    }

    let _ = Comment::update(&state.db, comment_id, &form.content).await;

    Redirect::to(&format!("/posts/{}", post_id))
}
