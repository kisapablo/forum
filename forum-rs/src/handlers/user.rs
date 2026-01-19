//! User profile and settings handlers
//!
//! Handles user profile pages, editing, and icon management.
use sailfish::TemplateOnce;

use axum::{
    extract::{Multipart, State},
    response::{Html, IntoResponse, Redirect},
    Form,
};
use serde::Deserialize;
use tower_sessions::Session;

use crate::{
    models::{Attachment, SessionUser, User},
    templates::user::*,
    utils::file_upload::save_uploaded_file,
    AppState,
};

/// Form data for updating user profile
#[derive(Debug, Deserialize)]
pub struct UpdateUserForm {
    pub name: Option<String>,
    pub password: Option<String>,
    pub moto: Option<String>,
}

/// Show personal cabinet
/// GET /user
pub async fn show_personal_cabinet(
    session: Session,
    State(state): State<AppState>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let user_info =
        User::get_user_info(&state.db, user.id).await.ok().flatten();
    let icon = Attachment::get_user_icon(&state.db, user.id)
        .await
        .ok()
        .flatten();

    let template = PersonalCabinetTemplate {
        user: Some(user),
        cabinet: user_info,
        icons: icon,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Show user editor
/// GET /user/UserEditor
pub async fn show_user_editor(
    session: Session,
    State(state): State<AppState>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let user_data = User::find_by_id(&state.db, user.id).await.ok().flatten();

    let template = UserEditorTemplate {
        user: Some(user),
        user_data,
        message: None,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Update user profile
/// POST /user/UserEditor/debug
pub async fn update_user_info(
    session: Session,
    State(state): State<AppState>,
    mut multipart: Multipart,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let mut name: Option<String> = None;
    let mut password: Option<String> = None;
    let mut moto: Option<String> = None;
    let mut avatar_path: Option<String> = None;

    // Parse multipart form
    while let Ok(Some(field)) = multipart.next_field().await {
        let field_name = field.name().unwrap_or("").to_string();

        match field_name.as_str() {
            "name" => {
                let text = field.text().await.unwrap_or_default();
                if !text.is_empty() {
                    name = Some(text);
                }
            }
            "password" => {
                let text = field.text().await.unwrap_or_default();
                if !text.is_empty() {
                    password = Some(text);
                }
            }
            "moto" => {
                let text = field.text().await.unwrap_or_default();
                if !text.is_empty() {
                    moto = Some(text);
                }
            }
            "avatar" => {
                if let Some(filename) = field.file_name() {
                    let filename = filename.to_string();
                    let data = field.bytes().await.unwrap_or_default();
                    if !data.is_empty() {
                        avatar_path =
                            save_uploaded_file(&data, &filename).await.ok();
                    }
                }
            }
            _ => {}
        }
    }

    // Update password if provided
    let (password_hash, password_salt) = if let Some(pwd) = password {
        match User::hash_password(&pwd) {
            Ok(hash) => (Some(hash), Some(String::new())),
            Err(_) => (None, None),
        }
    } else {
        (None, None)
    };

    // Update user profile
    let _ = User::update_profile(
        &state.db,
        user.id,
        name.as_deref(),
        password_hash.as_deref(),
        password_salt.as_deref(),
        moto.as_deref(),
    )
    .await;

    // Update icon if uploaded
    if let Some(path) = avatar_path {
        if let Ok(Some(icon_id)) =
            Attachment::add_user_icon(&state.db, &path, user.id, false).await
        {
            let _ = User::update_icon(&state.db, user.id, icon_id).await;
        }
    }

    // If username changed, update session or logout
    if name.is_some() {
        let _ = session.remove::<SessionUser>("user").await;
        return Redirect::to("/user/login").into_response();
    }

    Redirect::to("/user").into_response()
}

/// Show default icon selector
/// GET /user/icons/default
pub async fn show_default_icons(
    session: Session,
    State(state): State<AppState>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };

    let icons = Attachment::get_default_icons(&state.db)
        .await
        .unwrap_or_default();

    let template = SelectAvatarTemplate {
        user: Some(user),
        icons,
    };

    Html(template.render_once().unwrap()).into_response()
}

/// Set selected default icon
/// POST /user/icons/debug
pub async fn set_default_icon(
    session: Session,
    State(state): State<AppState>,
    Form(form): Form<std::collections::HashMap<String, String>>,
) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login"),
    };

    if let Some(icon_id_str) = form.get("icon_id") {
        if let Ok(icon_id) = icon_id_str.parse::<i64>() {
            let _ = User::update_icon(&state.db, user.id, icon_id).await;
        }
    }

    Redirect::to("/user")
}

/// Show bug report form
/// GET /user/report
pub async fn show_bug_report(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let template = ReportTemplate {
        user,
        message: None,
    };

    Html(template.render_once().unwrap())
}

/// Submit bug report
/// POST /user/report/debug
pub async fn submit_bug_report(
    session: Session,
    Form(_form): Form<std::collections::HashMap<String, String>>,
) -> impl IntoResponse {
    let _user = session.get::<SessionUser>("user").await.ok().flatten();

    // In a real application, you would save this to a database or send an email
    // For now, just redirect back with a success message

    Redirect::to("/user/report")
}
