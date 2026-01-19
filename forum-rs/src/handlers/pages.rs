// handlers/pages.rs
use axum::{
    extract::State,
    response::{Html, IntoResponse},
};
use sailfish::TemplateOnce;
use tower_sessions::Session;

use crate::{
    models::{Role, SessionUser, User},
    templates::{pages::*, NotFoundTemplate},
    AppState,
};

/// Show about page
/// GET /about
pub async fn show_about_page(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let template = AboutTemplate { user };

    Html(template.render_once().unwrap())
}

/// Show admin panel
/// GET /admin
pub async fn show_admin_panel(
    session: Session,
    State(state): State<AppState>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => {
            return Html(
                "<h1>Access Denied</h1><p>Please login as admin.</p>"
                    .to_string(),
            )
        }
    };

    // Check if user is admin
    let user_data = match User::find_by_id(&state.db, user.id).await {
        Ok(Some(u)) => u,
        _ => return Html("<h1>Error</h1><p>User not found.</p>".to_string()),
    };

    if user_data.role_id != Role::ADMIN {
        return Html(
            "<h1>Access Denied</h1><p>Admin access required.</p>".to_string(),
        );
    }

    let template = AdminTemplate { user: Some(user) };

    Html(template.render_once().unwrap())
}

/// 404 Not Found handler
pub async fn not_found(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let template = NotFoundTemplate { user };

    Html(template.render_once().unwrap())
}
