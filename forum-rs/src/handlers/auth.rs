//! Authentication handlers
//!
//! Handles user login, registration, and logout functionality.

use axum::{
    extract::State,
    response::{Html, IntoResponse, Redirect},
    Form,
};
use sailfish::TemplateOnce;
use serde::Deserialize;
use tower_sessions::Session;

use crate::{
    models::{Role, SessionUser, User},
    templates::auth::*,
    AppState,
};

/// Login form data
#[derive(Debug, Deserialize)]
pub struct LoginForm {
    pub login: String,
    pub password: String,
}

/// Registration form data
#[derive(Debug, Deserialize)]
pub struct RegisterForm {
    pub login: String,
    pub password: String,
}

/// Show login page
/// GET /user/login
pub async fn show_login_page(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let template = LoginTemplate {
        user,
        message: None,
    };

    Html(template.render_once().unwrap())
}

/// Process user login
/// POST /user/login
pub async fn login(
    session: Session,
    State(state): State<AppState>,
    Form(form): Form<LoginForm>,
) -> impl IntoResponse {
    // Find user
    let user = match User::find_by_name(&state.db, &form.login).await {
        Ok(Some(user)) => user,
        Ok(None) => {
            let template = LoginTemplate {
                user: None,
                message: Some("Invalid username or password".to_string()),
            };
            return Html(template.render_once().unwrap());
        }
        Err(_) => {
            let template = LoginTemplate {
                user: None,
                message: Some("Database error".to_string()),
            };
            return Html(template.render_once().unwrap());
        }
    };

    // Verify password
    if !user.verify_password(&form.password) {
        let template = LoginTemplate {
            user: None,
            message: Some("Invalid username or password".to_string()),
        };
        return Html(template.render_once().unwrap());
    }

    // Update last visit
    let _ = User::update_last_visit(&state.db, user.id).await;

    // Store user in session
    let session_user = SessionUser {
        id: user.id,
        name: user.name.clone(),
    };

    let _ = session.insert("user", session_user).await;

    // Redirect to personal cabinet
    let template = LoginTemplate {
        user: Some(SessionUser {
            id: user.id,
            name: user.name,
        }),
        message: Some("Login successful! Redirecting...".to_string()),
    };

    Html(template.render_once().unwrap())
}

/// Show registration page
/// GET /user/registration
pub async fn show_registration_page(session: Session) -> impl IntoResponse {
    let user = session.get::<SessionUser>("user").await.ok().flatten();

    let template = RegisterTemplate {
        user,
        message: None,
    };

    Html(template.render_once().unwrap())
}

/// Process user registration
/// POST /user/registration
pub async fn register(
    session: Session,
    State(state): State<AppState>,
    Form(form): Form<RegisterForm>,
) -> impl IntoResponse {
    // Validate input
    if form.login.is_empty() || form.password.is_empty() {
        let template = RegisterTemplate {
            user: None,
            message: Some("Username and password are required".to_string()),
        };
        return Html(template.render_once().unwrap());
    }

    if form.login.len() > 32 {
        let template = RegisterTemplate {
            user: None,
            message: Some("Username must be 32 characters or less".to_string()),
        };
        return Html(template.render_once().unwrap());
    }

    // Check if user already exists
    if let Ok(Some(_)) = User::find_by_name(&state.db, &form.login).await {
        let template = RegisterTemplate {
            user: None,
            message: Some("Username already taken".to_string()),
        };
        return Html(template.render_once().unwrap());
    }

    // Hash password
    let password_hash = match User::hash_password(&form.password) {
        Ok(hash) => hash,
        Err(_) => {
            let template = RegisterTemplate {
                user: None,
                message: Some("Error hashing password".to_string()),
            };
            return Html(template.render_once().unwrap());
        }
    };

    // Create user (using empty salt as bcrypt includes salt)
    let user_id = match User::create(
        &state.db,
        &form.login,
        &password_hash,
        "",
        Role::USER,
    )
    .await
    {
        Ok(id) => id,
        Err(_) => {
            let template = RegisterTemplate {
                user: None,
                message: Some("Error creating user".to_string()),
            };
            return Html(template.render_once().unwrap());
        }
    };

    // Store user in session
    let session_user = SessionUser {
        id: user_id,
        name: form.login.clone(),
    };

    let _ = session.insert("user", session_user.clone()).await;

    let template = RegisterTemplate {
        user: Some(session_user),
        message: Some("Registration successful!".to_string()),
    };

    Html(template.render_once().unwrap())
}

/// Logout user
/// GET /user/logout
pub async fn logout(session: Session) -> impl IntoResponse {
    let _ = session.remove::<SessionUser>("user").await;
    Redirect::to("/")
}
