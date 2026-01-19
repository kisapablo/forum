//! Post handlers
//!
//! Handles all post-related operations including:
//! - Listing posts (with pagination, search, filters)
//! - Creating, viewing, editing, and deleting posts
//! - Managing post attachments
use sailfish::TemplateOnce;

use axum::{
    extract::{Multipart, Path, Query, State},
    response::{Html, IntoResponse, Redirect},
    Form,
};
use serde::Deserialize;
use tower_sessions::Session;

use crate::{
    models::{Attachment, Comment, Post, PostWithTags, SessionUser, Tag},
    templates::posts::*,
    utils::file_upload::save_uploaded_file,
    AppState,
};

/// Query parameters for post listing
#[derive(Debug, Deserialize)]
pub struct PostQuery {
    #[serde(default = "default_page")]
    pub page: i64,
    pub author: Option<i64>,
    pub search: Option<String>,
    pub tags: Option<i32>,
}

fn default_page() -> i64 {
    1
}

/// Form data for creating a post
#[derive(Debug, Deserialize)]
pub struct CreatePostForm {
    pub title: String,
    pub content: String,
    pub tags: Option<String>,
}

/// Show all posts (homepage)
/// GET / or GET /posts
pub async fn show_all_posts(
    session: Session,
    State(state): State<AppState>,
    Query(query): Query<PostQuery>,
) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    // Get posts with pagination
    let posts = Post::get_all_paginated(
        &state.db,
        query.page,
        query.author,
        query.search.as_deref(),
        query.tags,
    )
    .await
    .unwrap_or_default();

    // Get tags for each post
    let mut posts_with_tags = Vec::new();
    for post in posts {
        let tags = Tag::get_by_post(&state.db, post.id)
            .await
            .unwrap_or_default();
        posts_with_tags.push(PostWithTags { post, tags });
    }

    // Calculate pagination
    let pagination = Post::calculate_pagination(
        &state.db,
        query.page,
        query.author,
        query.search.as_deref(),
        query.tags,
    )
    .await
    .unwrap_or_default();

    let template = IndexTemplate {
        user,
        posts: posts_with_tags,
        pagination,
        search_term: query.search,
    };

    Html(template.render_once().unwrap())
}

/// Show single post page
/// GET /posts/{post_id}
pub async fn show_post_page(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    // Get post
    let post = match Post::find_with_author(&state.db, post_id).await {
        Ok(Some(post)) => post,
        _ => {
            let template = NotFoundTemplate { user };
            return Html(template.render_once().unwrap());
        }
    };

    // Get tags
    let tags = Tag::get_by_post(&state.db, post_id)
        .await
        .unwrap_or_default();

    // Get post attachments
    let post_attachments = Attachment::get_post_attachments(&state.db, post_id)
        .await
        .unwrap_or_default();

    // Get comments
    let comments = Comment::get_by_post(&state.db, post_id)
        .await
        .unwrap_or_default();

    // Get comment attachments for all comments
    let mut comments_with_attachments = Vec::new();
    for comment in comments {
        let attachments =
            Attachment::get_comment_attachments(&state.db, comment.id)
                .await
                .unwrap_or_default();
        comments_with_attachments.push(crate::models::CommentWithAttachments {
            comment,
            attachments,
        });
    }

    let template = PostPageTemplate {
        user,
        post: PostWithTags { post, tags },
        post_attachments,
        comments: comments_with_attachments,
    };

    Html(template.render_once().unwrap())
}

/// Show post creation form
/// GET /posts/builders
pub async fn show_post_builder(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    if user.is_none() {
        return Redirect::to("/user/login").into_response();
    }

    let template = CreatePostTemplate {
        user,
        message: None,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Create new post
/// POST /posts
pub async fn create_post(
    session: Session,
    State(state): State<AppState>,
    mut multipart: Multipart,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let mut title = String::new();
    let mut content = String::new();
    let mut tags = String::new();
    let mut attachment_path: Option<String> = None;

    // Parse multipart form
    while let Ok(Some(field)) = multipart.next_field().await {
        let name = field.name().unwrap_or("").to_string();

        match name.as_str() {
            "title" => {
                title = field.text().await.unwrap_or_default();
            }
            "content" => {
                content = field.text().await.unwrap_or_default();
            }
            "tags" => {
                tags = field.text().await.unwrap_or_default();
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

    // Validate
    if title.is_empty() || content.is_empty() {
        let template = CreatePostTemplate {
            user: Some(user),
            message: Some("Title and content are required".to_string()),
        };
        return Html(template.render_once().unwrap()).into_response();
    }

    // Create post
    let post_id = match Post::create(&state.db, &title, &content, user.id).await
    {
        Ok(id) => id,
        Err(_) => {
            let template = CreatePostTemplate {
                user: Some(user),
                message: Some("Error creating post".to_string()),
            };
            return Html(template.render_once().unwrap()).into_response();
        }
    };

    // Add attachment if uploaded
    if let Some(path) = attachment_path {
        let _ =
            Attachment::add_post_attachment(&state.db, &path, post_id).await;
    }

    // Add tags
    if !tags.is_empty() {
        for tag_name in tags.split(',') {
            let tag_name = tag_name.trim();
            if !tag_name.is_empty() {
                if let Ok(tag_id) =
                    Tag::find_or_create(&state.db, tag_name).await
                {
                    let _ = Tag::add_to_post(&state.db, tag_id, post_id).await;
                }
            }
        }
    }

    Redirect::to(&format!("/posts/{}", post_id)).into_response()
}

/// Show post deletion confirmation
/// GET /posts/delete/{post_id}
pub async fn show_delete_post(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let post = match Post::find_by_id(&state.db, post_id).await {
        Ok(Some(p)) => p,
        _ => return Redirect::to("/").into_response(),
    };

    // Check ownership
    if post.author_id != user.id {
        return Redirect::to("/").into_response();
    }

    let template = DeletePostTemplate {
        user: Some(user),
        post_id,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Delete post
/// POST /posts/delete/{post_id}/initial
pub async fn delete_post(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    let post = match Post::find_by_id(&state.db, post_id).await {
        Ok(Some(p)) => p,
        _ => return Redirect::to("/"),
    };

    // Check ownership
    if post.author_id != user.id {
        return Redirect::to("/");
    }

    // Delete comments first (cascade)
    let _ = Comment::delete_by_post(&state.db, post_id).await;

    // Delete post
    let _ = Post::delete(&state.db, post_id).await;

    Redirect::to("/")
}

/// Show post editor
/// GET /user/posts/PostEditor/{post_id}
pub async fn show_post_editor(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let post = match Post::find_by_id(&state.db, post_id).await {
        Ok(Some(p)) => p,
        _ => return Redirect::to("/").into_response(),
    };

    // Check ownership
    if post.author_id != user.id {
        return Redirect::to("/").into_response();
    }

    let template = PostEditorTemplate {
        user: Some(user),
        post,
        message: None,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Update post
/// POST /user/posts/PostEditor/{post_id}
pub async fn update_post(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
    Form(form): Form<CreatePostForm>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    let post = match Post::find_by_id(&state.db, post_id).await {
        Ok(Some(p)) => p,
        _ => return Redirect::to("/"),
    };

    // Check ownership
    if post.author_id != user.id {
        return Redirect::to("/");
    }

    // Update post
    let _ = Post::update(&state.db, post_id, &form.title, &form.content).await;

    Redirect::to(&format!("/posts/{}", post_id))
}

/// Show karma leaderboard
/// GET /posts/leaders
pub async fn show_karma_leaders(
    session: Session,
    State(state): State<AppState>,
) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let leaders = crate::models::User::get_karma_leaders(&state.db)
        .await
        .unwrap_or_default();

    let template = LeaderKarmaTemplate { user, leaders };

    Html(template.render_once().unwrap())
}
