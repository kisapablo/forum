# Forum-RS

A modern forum web application written in Rust, rewritten from the original PHP implementation. This project demonstrates best practices in Rust web development and is designed to be maintained by junior developers.

## Overview

Forum-RS is a full-featured forum application that allows users to create accounts, post content, comment on posts, and interact with the community. It features a clean architecture, type-safe database queries, and secure authentication.

### Key Features

- **User Authentication**: Secure registration and login with bcrypt password hashing
- **Post Management**: Create, read, update, and delete forum posts
- **Commenting System**: Add comments to posts with attachment support
- **File Uploads**: Upload images for posts, comments, and user avatars
- **User Profiles**: Customize profile with avatar and personal motto
- **Tag System**: Organize posts with tags for easy categorization
- **Search & Filtering**: Find posts by title, author, or tags
- **Pagination**: Browse posts with paginated views (3 posts per page)
- **Karma System**: Track user reputation based on post count
- **Admin Panel**: Administrative interface for forum management
- **Responsive Design**: Bootstrap-based UI that works on all devices

## Technology Stack

### Core Technologies

- **[Rust](https://www.rust-lang.org/)**: Systems programming language focused on safety and performance
- **[Axum](https://github.com/tokio-rs/axum)**: Ergonomic and modular web framework
- **[SQLx](https://github.com/launchbadge/sqlx)**: Async, pure Rust SQL toolkit with compile-time checked queries
- **[Sailfish](https://github.com/rust-sailfish/sailfish)**: High-performance template engine
- **[Tokio](https://tokio.rs/)**: Asynchronous runtime for Rust
- **[MySQL](https://www.mysql.com/)**: Relational database

### Additional Libraries

- **bcrypt**: Secure password hashing
- **tower-sessions**: Session management middleware
- **serde**: Serialization/deserialization framework
- **chrono**: Date and time handling
- **tracing**: Application-level tracing and logging

## Project Structure

```
forum-rs/
├── bin/
│   └── main.rs                 # Application entry point, server setup, routing
├── src/
│   ├── lib.rs                  # Library root, exports public API
│   ├── handlers/               # HTTP request handlers (controllers)
│   │   ├── mod.rs
│   │   ├── auth.rs            # Login, registration, logout
│   │   ├── posts.rs           # Post CRUD operations
│   │   ├── comments.rs        # Comment management
│   │   ├── user.rs            # User profile and settings
│   │   └── pages.rs           # Static pages (about, admin, 404)
│   ├── models/                # Database models and data access
│   │   ├── mod.rs
│   │   ├── user.rs            # User model and queries
│   │   ├── post.rs            # Post model and queries
│   │   ├── comment.rs         # Comment model and queries
│   │   ├── attachment.rs      # File attachment model
│   │   ├── tag.rs             # Tag model
│   │   └── role.rs            # User role model
│   ├── templates/             # Template definitions (Sailfish structs)
│   │   ├── mod.rs
│   │   ├── auth.rs            # Auth template structs
│   │   ├── posts.rs           # Post template structs
│   │   ├── comments.rs        # Comment template structs
│   │   ├── user.rs            # User profile template structs
│   │   └── pages.rs           # Static page template structs
│   ├── middleware/            # Custom middleware
│   │   ├── mod.rs
│   │   └── auth.rs            # Authentication middleware
│   └── utils/                 # Utility functions
│       ├── mod.rs
│       └── file_upload.rs     # File upload handling
├── templates/                  # Sailfish HTML templates (.stpl files)
│   ├── index.stpl             # Homepage/post list
│   ├── post.stpl              # Single post view
│   ├── user-login.stpl        # Login page
│   ├── user-registration.stpl # Registration page
│   └── ...                    # Other templates
├── static/                     # Static assets
│   ├── css/                   # Stylesheets
│   └── images/                # Uploaded images
├── migrations/                 # Database migrations (if using sqlx migrate)
├── Cargo.toml                  # Rust dependencies and project metadata
├── Dockerfile                  # Container image definition
├── docker-compose.yml          # Multi-container Docker setup
└── README.md                   # This file
```

## Getting Started

### Prerequisites

- Rust 1.75 or later ([Install Rust](https://www.rust-lang.org/tools/install))
- MySQL 8.0 or later
- Docker and Docker Compose (optional, for containerized deployment)

### Local Development Setup

1. **Clone the repository**
   ```bash
   cd forum-rs
   ```

2. **Set up the database**
   
   Create a MySQL database and run the schema:
   ```bash
   mysql -u root -p < ../config/schema.sql
   ```

3. **Configure environment variables**
   
   Copy the example environment file and edit it:
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   DATABASE_URL=mysql://quasi:root@localhost/petos_forum_db
   HOST=0.0.0.0
   PORT=3000
   SESSION_SECRET=your-secret-key-here
   RUST_LOG=info,forum_rs=debug
   ```

4. **Build and run the application**
   ```bash
   cargo build --release
   cargo run --release
   ```

5. **Access the application**
   
   Open your browser and navigate to: `http://localhost:3000`

### Docker Deployment

The easiest way to run Forum-RS is using Docker Compose:

1. **Start all services**
   ```bash
   docker-compose up -d
   ```

2. **View logs**
   ```bash
   docker-compose logs -f app
   ```

3. **Stop services**
   ```bash
   docker-compose down
   ```

The application will be available at `http://localhost:3000`, and the database will be automatically initialized with the schema.

## API Endpoints

All endpoints from the original PHP application have been preserved:

### Authentication
- `GET /user/login` - Show login page
- `POST /user/login` - Process login
- `GET /user/registration` - Show registration page
- `POST /user/registration` - Process registration
- `GET /user/logout` - Logout user

### Posts
- `GET /` or `GET /posts` - List all posts (homepage)
- `GET /posts/{post_id}` - View single post
- `GET /posts/builders` - Show create post form (auth required)
- `POST /posts` - Create new post (auth required)
- `GET /posts/delete/{post_id}` - Show delete confirmation (auth required)
- `POST /posts/delete/{post_id}/initial` - Delete post (auth required)
- `GET /user/posts/PostEditor/{post_id}` - Show edit form (auth required)
- `POST /user/posts/PostEditor/{post_id}` - Update post (auth required)
- `GET /posts/leaders` - Show karma leaderboard

### Comments
- `POST /posts/{post_id}/comments` - Create comment (auth required)
- `GET /posts/delete/{post_id}/comments/{comment_id}` - Delete confirmation
- `POST /posts/delete/{post_id}/comments/{comment_id}/initial` - Delete comment
- `GET /user/posts/CommentEditor/{post_id}/{comment_id}` - Edit form
- `POST /user/posts/CommentEditor/{post_id}/{comment_id}` - Update comment

### User Profile
- `GET /user` - Personal cabinet (auth required)
- `GET /user/UserEditor` - Edit profile form (auth required)
- `POST /user/UserEditor/debug` - Update profile (auth required)
- `GET /user/icons/default` - Select default avatar (auth required)
- `POST /user/icons/debug` - Set avatar (auth required)
- `GET /user/report` - Bug report form
- `POST /user/report/debug` - Submit bug report

### Admin & Other
- `GET /admin` - Admin panel (admin role required)
- `GET /about` - About page

## Database Schema

The application uses the same database schema as the original PHP version:

### Core Tables
- **user**: User accounts with authentication info
- **post**: Forum posts
- **comment**: Comments on posts
- **attachment**: Generic file attachments
- **tag**: Post categorization tags
- **role**: User roles (Admin/User)

### Relationship Tables
- **post_attachment**: Links posts to attachments
- **comment_attachment**: Links comments to attachments
- **user_icon**: User avatar images
- **m2m_tag_post**: Many-to-many post-tag relationships

### Database Views
- **user_post_view**: Posts with author information
- **user_comment_view**: Comments with author information
- **post_attachment_view**: Post attachments with metadata
- **comment_attachment_view**: Comment attachments with metadata
- **user_icon_view**: User icons with metadata
- **post_tag_view**: Posts with their tags
- **search_tag_view**: Combined post, author, and tag data for search
- **user_info_view**: User statistics and role information

### Stored Procedures
- **AddPostAttachment**: Atomically create post attachment
- **AddCommentAttachment**: Atomically create comment attachment
- **AddUserIcon**: Create user icon with validation

## Development Guide for Junior Developers

### Understanding the Code Flow

1. **Request Flow**: HTTP Request → Router (main.rs) → Handler → Model → Database → Model → Handler → Template → HTTP Response

2. **Example: Viewing a Post**
   - User visits `/posts/123`
   - Router matches route in `main.rs:69`
   - Calls `handlers::posts::show_post_page`
   - Handler fetches post using `Post::find_with_author` model method
   - Model executes SQL query via SQLx
   - Handler prepares data and renders `PostPageTemplate`
   - Sailfish compiles template to HTML
   - HTML returned to user's browser

### Key Rust Concepts

#### Ownership and Borrowing
```rust
// Ownership: Only one owner at a time
let user = User { id: 1, name: "Alice".to_string() };

// Borrowing: Temporary read access with &
fn print_name(user: &User) {
    println!("{}", user.name);
}
print_name(&user); // user is borrowed, not moved
```

#### Result and Option Types
```rust
// Result<T, E>: Operation that can succeed (Ok) or fail (Err)
let user = User::find_by_id(&pool, 123).await?; // ? propagates errors

// Option<T>: Value that might be present (Some) or absent (None)
if let Some(user) = session.get::<User>("user").await? {
    // User is logged in
}
```

#### Async/Await
```rust
// async functions return futures that must be .await-ed
async fn get_user(id: i64) -> Result<User, Error> {
    let user = sqlx::query_as("SELECT * FROM user WHERE id = ?")
        .bind(id)
        .fetch_one(&pool)
        .await?; // Wait for database query to complete
    Ok(user)
}
```

### Common Tasks

#### Adding a New Endpoint

1. Add route in `bin/main.rs`:
   ```rust
   .route("/your/path", get(handlers::your_handler))
   ```

2. Create handler in appropriate `handlers/*.rs` file:
   ```rust
   pub async fn your_handler(
       session: Session,
       State(state): State<AppState>,
   ) -> impl IntoResponse {
       // Your logic here
   }
   ```

3. Create template struct in `src/templates/*.rs`:
   ```rust
   #[derive(TemplateOnce)]
   #[template(path = "your-template.stpl")]
   pub struct YourTemplate {
       pub user: Option<SessionUser>,
       pub data: Vec<YourData>,
   }
   ```

4. Create HTML template in `templates/your-template.stpl`

#### Adding a Database Query

1. Add method to appropriate model in `src/models/*.rs`:
   ```rust
   impl YourModel {
       pub async fn your_query(pool: &MySqlPool) -> Result<Vec<Self>, sqlx::Error> {
           sqlx::query_as::<_, Self>("SELECT * FROM your_table")
               .fetch_all(pool)
               .await
       }
   }
   ```

#### Working with Forms

Forms use `Form` extractor or `Multipart` for file uploads:
```rust
#[derive(Deserialize)]
pub struct YourForm {
    pub field_name: String,
}

pub async fn handle_form(Form(form): Form<YourForm>) -> impl IntoResponse {
    // Access form.field_name
}
```

### Debugging

1. **Enable detailed logging**:
   ```bash
   RUST_LOG=debug cargo run
   ```

2. **Use `dbg!` macro** for quick debugging:
   ```rust
   dbg!(&user); // Prints debug representation
   ```

3. **Check compile-time SQL queries**: SQLx verifies queries at compile time. If you get SQL errors during compilation, check your database connection and query syntax.

### Testing

Run tests with:
```bash
cargo test
```

Run specific test:
```bash
cargo test test_name
```

### Code Style

- Run formatter: `cargo fmt`
- Run linter: `cargo clippy`
- Fix issues: `cargo clippy --fix`

## Security Considerations

- **Password Hashing**: Bcrypt with default cost factor (12)
- **SQL Injection**: Prevented by SQLx parameterized queries
- **Session Security**: Secure cookie-based sessions with configurable expiry
- **File Upload Validation**: Filename sanitization to prevent directory traversal
- **CSRF Protection**: Should be implemented for production use
- **XSS Prevention**: Sailfish auto-escapes HTML by default

## Performance

- **Async I/O**: Non-blocking database and file operations
- **Connection Pooling**: SQLx manages database connection pool
- **Compile-time Templates**: Sailfish compiles templates to Rust code for maximum performance
- **Zero-cost Abstractions**: Rust's performance is comparable to C/C++

## Troubleshooting

### Database Connection Errors

- Verify MySQL is running: `systemctl status mysql`
- Check credentials in `.env` file
- Ensure database exists: `mysql -u root -p -e "SHOW DATABASES;"`

### Compilation Errors

- Update dependencies: `cargo update`
- Clean and rebuild: `cargo clean && cargo build`
- Check Rust version: `rustc --version` (need 1.75+)

### Template Errors

- Sailfish templates compile at build time
- Check syntax in `.stpl` files
- Ensure template path matches `#[template(path = "...")]`

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make your changes
4. Run tests: `cargo test`
5. Format code: `cargo fmt`
6. Run linter: `cargo clippy`
7. Commit changes: `git commit -am 'Add your feature'`
8. Push to branch: `git push origin feature/your-feature`
9. Submit a pull request

## License

This project is provided as-is for educational purposes.

## Resources for Learning

### Rust
- [The Rust Programming Language Book](https://doc.rust-lang.org/book/)
- [Rust by Example](https://doc.rust-lang.org/rust-by-example/)
- [Rustlings](https://github.com/rust-lang/rustlings) - Interactive exercises

### Axum
- [Axum Documentation](https://docs.rs/axum/latest/axum/)
- [Axum Examples](https://github.com/tokio-rs/axum/tree/main/examples)

### SQLx
- [SQLx Documentation](https://docs.rs/sqlx/latest/sqlx/)
- [SQLx GitHub](https://github.com/launchbadge/sqlx)

### Async Rust
- [Tokio Tutorial](https://tokio.rs/tokio/tutorial)
- [Async Book](https://rust-lang.github.io/async-book/)

## Support

For questions or issues:
- Check existing GitHub issues
- Read the inline documentation (comments in code)
- Consult the Rust documentation
- Ask in the Rust community forums

## Comparison with PHP Version

| Feature | PHP Version | Rust Version |
|---------|-------------|--------------|
| Framework | Slim 4 | Axum 0.7 |
| Template Engine | Twig 3 | Sailfish 0.9 |
| Database Library | PDO | SQLx 0.8 |
| Password Hashing | password_hash() | Bcrypt |
| Sessions | Native PHP sessions | Tower Sessions |
| Performance | Good | Excellent (10-100x faster) |
| Memory Safety | Runtime errors possible | Compile-time guarantees |
| Type Safety | Weak typing | Strong static typing |
| Concurrency | Limited | Built-in async/await |

---

Built with Rust - Safe, Fast, Concurrent
