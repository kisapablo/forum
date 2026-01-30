// bin/main.rs
//! Forum application entry point
//!
//! This file sets up and runs the Axum web server with all routes and middleware.

use axum::{
    routing::{get, post},
    Router,
};
use forum_rs::{create_db_pool, handlers, AppState};
use tower_http::{services::ServeDir, trace::TraceLayer};
use tower_sessions::cookie::time::Duration;
use tower_sessions::MemoryStore;
use tower_sessions::{Expiry, SessionManagerLayer};
use tracing_subscriber::{layer::SubscriberExt, util::SubscriberInitExt};

#[tokio::main]
async fn main() {
    // Load environment variables from .env file
    dotenvy::dotenv().ok();

    // Initialize tracing for logging
    tracing_subscriber::registry()
        .with(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| {
                    "forum_rs=debug,tower_http=debug,axum=trace,sqlx=warn"
                        .into()
                }),
        )
        .with(tracing_subscriber::fmt::layer())
        .init();

    // Create database connection pool
    let db = create_db_pool()
        .await
        .expect("Failed to connect to database");

    tracing::info!("Connected to database successfully");

    // Create application state
    let state = AppState::new(db);

    // Set up session store
    let session_store = MemoryStore::default();
    let session_layer = SessionManagerLayer::new(session_store)
        .with_expiry(Expiry::OnInactivity(Duration::hours(24)));

    // Build the router with all endpoints
    let app = Router::new()
        // Homepage
        .route("/", get(handlers::show_all_posts))
        // Posts routes
        .route("/posts", get(handlers::show_all_posts))
        .route("/posts/:post_id", get(handlers::show_post_page))
        .route("/posts/builders", get(handlers::show_post_builder))
        .route("/posts", post(handlers::create_post))
        .route("/posts/delete/:post_id", get(handlers::show_delete_post))
        .route(
            "/posts/delete/:post_id/initial",
            post(handlers::delete_post),
        )
        .route("/posts/leaders", get(handlers::show_karma_leaders))
        .route(
            "/user/posts/PostEditor/:post_id",
            get(handlers::show_post_editor),
        )
        .route(
            "/user/posts/PostEditor/:post_id",
            post(handlers::update_post),
        )
        // Comment routes
        .route("/posts/:post_id/comments", post(handlers::create_comment))
        .route(
            "/posts/delete/:post_id/comments/:comment_id",
            get(handlers::show_delete_comment),
        )
        .route(
            "/posts/delete/:post_id/comments/:comment_id/initial",
            post(handlers::delete_comment),
        )
        .route(
            "/user/posts/CommentEditor/:post_id/:comment_id",
            get(handlers::show_comment_editor),
        )
        .route(
            "/user/posts/CommentEditor/:post_id/:comment_id",
            post(handlers::update_comment),
        )
        // Auth routes
        .route("/user/login", get(handlers::show_login_page))
        .route("/user/login", post(handlers::login))
        .route("/user/registration", get(handlers::show_registration_page))
        .route("/user/registration", post(handlers::register))
        .route("/user/logout", get(handlers::logout))
        // User routes
        .route("/user", get(handlers::show_personal_cabinet))
        .route("/user/UserEditor", get(handlers::show_user_editor))
        .route("/user/UserEditor/debug", post(handlers::update_user_info))
        .route("/user/icons/default", get(handlers::show_default_icons))
        .route("/user/icons/debug", post(handlers::set_default_icon))
        .route("/user/report", get(handlers::show_bug_report))
        .route("/user/report/debug", post(handlers::submit_bug_report))
        // Admin route
        .route("/admin", get(handlers::show_admin_panel))
        // About page
        .route("/about", get(handlers::show_about_page))
        // Static files
        .nest_service("/static", ServeDir::new("forum-rs/static"))
        .nest_service("/public", ServeDir::new("forum-rs/static"))
        // Add state, session layer, and tracing
        .layer(session_layer)
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    // Get host and port from environment
    let host = std::env::var("HOST").unwrap_or_else(|_| "0.0.0.0".to_string());
    let port = std::env::var("PORT").unwrap_or_else(|_| "3000".to_string());
    let addr = format!("{}:{}", host, port);

    tracing::info!("Starting server on http://{}", addr);

    // Run the server
    let listener = tokio::net::TcpListener::bind(&addr)
        .await
        .expect("Failed to bind to address");

    axum::serve(listener, app)
        .await
        .expect("Server failed to start");
}

