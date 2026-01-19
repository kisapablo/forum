# Developer Documentation

## Architecture Overview

Forum-RS follows a **layered architecture** pattern, separating concerns into distinct modules:

```
┌─────────────────────────────────────────┐
│         HTTP Clients (Browsers)         │
└──────────────────┬──────────────────────┘
                   │ HTTP Request
                   ▼
┌─────────────────────────────────────────┐
│         Axum Web Framework              │
│  (Routing, Middleware, Sessions)        │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│          Handlers Layer                 │
│  (HTTP Request/Response Processing)     │
│  - auth.rs: Authentication              │
│  - posts.rs: Post management            │
│  - comments.rs: Comment management      │
│  - user.rs: User profiles               │
│  - pages.rs: Static pages               │
└──────────────────┬──────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────┐
│          Models Layer                   │
│  (Business Logic & Data Access)         │
│  - user.rs: User operations             │
│  - post.rs: Post operations             │
│  - comment.rs: Comment operations       │
│  - attachment.rs: File operations       │
│  - tag.rs: Tag operations               │
│  - role.rs: Role operations             │
└──────────────────┬──────────────────────┘
                   │ SQLx Queries
                   ▼
┌─────────────────────────────────────────┐
│         MySQL Database                  │
│  (Data Persistence)                     │
└─────────────────────────────────────────┘
```

## Module Responsibilities

### 1. Handlers (`src/handlers/`)

**Purpose**: Process HTTP requests and return responses

**Responsibilities**:
- Extract data from HTTP requests (path params, query strings, form data)
- Validate user input
- Call model methods to fetch/modify data
- Prepare data for templates
- Render templates or return redirects
- Handle errors gracefully

**Example**:
```rust
// handlers/posts.rs
pub async fn show_post_page(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    // 1. Get current user from session
    let user = session.get::<SessionUser>("user").await.ok().flatten();
    
    // 2. Fetch post from database using model
    let post = match Post::find_with_author(&state.db, post_id).await {
        Ok(Some(post)) => post,
        _ => return Html(render_404(user)),
    };
    
    // 3. Fetch related data (comments, tags)
    let comments = Comment::get_by_post(&state.db, post_id).await.unwrap_or_default();
    let tags = Tag::get_by_post(&state.db, post_id).await.unwrap_or_default();
    
    // 4. Render template with data
    let template = PostPageTemplate { user, post, comments, tags };
    Html(sailfish::render!(template).unwrap())
}
```

### 2. Models (`src/models/`)

**Purpose**: Represent database entities and handle data access

**Responsibilities**:
- Define data structures (structs) matching database tables
- Provide methods for CRUD operations (Create, Read, Update, Delete)
- Execute SQL queries using SQLx
- Handle database errors
- Implement business logic related to data

**Example**:
```rust
// models/post.rs
impl Post {
    /// Find post by ID
    pub async fn find_by_id(pool: &MySqlPool, post_id: i64) -> Result<Option<Self>, sqlx::Error> {
        sqlx::query_as::<_, Post>("SELECT * FROM post WHERE id = ?")
            .bind(post_id)
            .fetch_optional(pool)
            .await
    }
    
    /// Create new post
    pub async fn create(
        pool: &MySqlPool,
        title: &str,
        content: &str,
        author_id: i64,
    ) -> Result<i64, sqlx::Error> {
        let result = sqlx::query(
            "INSERT INTO post (title, content, author_id) VALUES (?, ?, ?)"
        )
        .bind(title)
        .bind(content)
        .bind(author_id)
        .execute(pool)
        .await?;
        
        Ok(result.last_insert_id() as i64)
    }
}
```

### 3. Templates (`src/templates/` + `templates/`)

**Purpose**: Define and render HTML views

**Responsibilities**:
- Define template data structures (in `src/templates/`)
- Specify which template file to use
- Render HTML with data (Sailfish does this automatically)

**Example**:
```rust
// src/templates/posts.rs
#[derive(TemplateOnce)]
#[template(path = "post.stpl")]
pub struct PostPageTemplate {
    pub user: Option<SessionUser>,
    pub post: PostWithTags,
    pub comments: Vec<CommentWithAttachments>,
}
```

```html
<!-- templates/post.stpl -->
<h1><%= &post.post.title %></h1>
<p><%= &post.post.content %></p>

<% for comment in &comments { %>
    <div class="comment">
        <%= &comment.comment.content %>
    </div>
<% } %>
```

### 4. Middleware (`src/middleware/`)

**Purpose**: Process requests before they reach handlers

**Responsibilities**:
- Authentication checks
- Logging
- Request/response modification
- Error handling

**Example**:
```rust
// middleware/auth.rs
pub async fn require_auth(session: Session) -> Result<(), Response> {
    match session.get::<SessionUser>("user").await {
        Ok(Some(_)) => Ok(()), // User is authenticated
        _ => Err(Redirect::to("/user/login").into_response()),
    }
}
```

### 5. Utils (`src/utils/`)

**Purpose**: Shared utility functions

**Responsibilities**:
- File upload handling
- Data validation
- Format conversion
- Common helper functions

## Data Flow Examples

### Creating a Post

```
1. User submits form on /posts/builders
   └─> POST /posts with multipart form data
   
2. Axum routes to handlers::posts::create_post
   └─> Extracts form fields and file upload
   
3. Handler validates input
   └─> Title and content not empty
   └─> File type is allowed
   
4. Handler saves uploaded file
   └─> utils::file_upload::save_uploaded_file()
   └─> Returns file path
   
5. Handler creates post in database
   └─> Post::create(&db, title, content, author_id)
   └─> Executes INSERT query via SQLx
   └─> Returns new post ID
   
6. Handler adds attachment to post
   └─> Attachment::add_post_attachment(&db, file_path, post_id)
   └─> Calls stored procedure
   
7. Handler adds tags to post
   └─> For each tag: Tag::find_or_create() then Tag::add_to_post()
   
8. Handler redirects to new post
   └─> Redirect::to(&format!("/posts/{}", post_id))
   
9. Browser follows redirect
   └─> GET /posts/{post_id}
   └─> User sees their new post
```

### User Login

```
1. User visits /user/login
   └─> GET request
   
2. Axum routes to handlers::auth::show_login_page
   └─> Renders login form template
   
3. User submits login form
   └─> POST /user/login with username and password
   
4. Axum routes to handlers::auth::login
   └─> Extracts form data
   
5. Handler finds user by username
   └─> User::find_by_name(&db, username)
   └─> Executes SELECT query
   
6. Handler verifies password
   └─> user.verify_password(password)
   └─> Uses bcrypt to compare hash
   
7. If password correct:
   └─> Create SessionUser { id, name }
   └─> session.insert("user", session_user)
   └─> Update last_visit_date in database
   └─> Redirect to /user (personal cabinet)
   
8. If password incorrect:
   └─> Re-render login form with error message
```

## Database Interaction

### Using SQLx

SQLx provides **compile-time checked queries**, meaning SQL errors are caught during compilation, not at runtime.

#### Basic Query

```rust
// Fetch one row
let user: User = sqlx::query_as("SELECT * FROM user WHERE id = ?")
    .bind(user_id)
    .fetch_one(&pool)  // Returns error if not found
    .await?;

// Fetch optional row
let user: Option<User> = sqlx::query_as("SELECT * FROM user WHERE id = ?")
    .bind(user_id)
    .fetch_optional(&pool)  // Returns None if not found
    .await?;

// Fetch multiple rows
let posts: Vec<Post> = sqlx::query_as("SELECT * FROM post WHERE author_id = ?")
    .bind(author_id)
    .fetch_all(&pool)
    .await?;
```

#### Insert/Update/Delete

```rust
// Insert
let result = sqlx::query("INSERT INTO post (title, content, author_id) VALUES (?, ?, ?)")
    .bind(title)
    .bind(content)
    .bind(author_id)
    .execute(&pool)
    .await?;

let inserted_id = result.last_insert_id();

// Update
sqlx::query("UPDATE post SET title = ?, content = ? WHERE id = ?")
    .bind(new_title)
    .bind(new_content)
    .bind(post_id)
    .execute(&pool)
    .await?;

// Delete
sqlx::query("DELETE FROM post WHERE id = ?")
    .bind(post_id)
    .execute(&pool)
    .await?;
```

#### Calling Stored Procedures

```rust
let result: Option<(Option<i64>,)> = sqlx::query_as(
    "CALL AddPostAttachment(?, ?, @attachment_id); SELECT @attachment_id"
)
.bind(file_name)
.bind(post_id)
.fetch_optional(&pool)
.await?;

let attachment_id = result.and_then(|(id,)| id);
```

### Database Views

Views are pre-defined SQL queries that act like tables. They're useful for joining related data.

```rust
// user_post_view joins post and user tables
let posts: Vec<PostWithAuthor> = sqlx::query_as(
    "SELECT * FROM user_post_view WHERE author_id = ?"
)
.bind(author_id)
.fetch_all(&pool)
.await?;

// This automatically gives us author name and icon without manual joins
```

## Session Management

Sessions store user state across HTTP requests (which are stateless).

### Setting Session Data

```rust
let session_user = SessionUser {
    id: user.id,
    name: user.name.clone(),
};

// Store in session (automatically serialized to JSON)
session.insert("user", session_user).await?;
```

### Getting Session Data

```rust
// Get session data
let user: Option<SessionUser> = session
    .get::<SessionUser>("user")
    .await
    .ok()
    .flatten();

if let Some(user) = user {
    // User is logged in
} else {
    // User is not logged in
}
```

### Removing Session Data (Logout)

```rust
session.remove::<SessionUser>("user").await?;
```

## Error Handling

Rust uses `Result<T, E>` for operations that can fail.

### The `?` Operator

```rust
// Without ?
let user = match User::find_by_id(&pool, id).await {
    Ok(Some(user)) => user,
    Ok(None) => return Err("User not found"),
    Err(e) => return Err(e),
};

// With ?
let user = User::find_by_id(&pool, id).await?
    .ok_or("User not found")?;
```

The `?` operator:
1. If `Ok(value)`, unwraps to `value`
2. If `Err(error)`, returns early with the error

### Handling Errors in Handlers

```rust
pub async fn handler(State(state): State<AppState>) -> impl IntoResponse {
    // Return 404 if post not found
    let post = match Post::find_by_id(&state.db, post_id).await {
        Ok(Some(post)) => post,
        Ok(None) => return render_404(),
        Err(_) => return render_error("Database error"),
    };
    
    // ... use post
}
```

## File Uploads

### Receiving Uploads

```rust
pub async fn handle_upload(mut multipart: Multipart) -> impl IntoResponse {
    let mut file_data: Option<Bytes> = None;
    let mut filename: Option<String> = None;
    
    // Parse multipart form
    while let Ok(Some(field)) = multipart.next_field().await {
        if field.name() == Some("file") {
            filename = field.file_name().map(|s| s.to_string());
            file_data = Some(field.bytes().await.unwrap_or_default());
        }
    }
    
    // Save file
    if let (Some(data), Some(name)) = (file_data, filename) {
        let path = save_uploaded_file(&data, &name).await?;
        // Store path in database
    }
}
```

### Saving Files Securely

```rust
// utils/file_upload.rs
pub async fn save_uploaded_file(data: &Bytes, filename: &str) -> Result<String, Error> {
    // 1. Sanitize filename (remove path separators, special chars)
    let safe_name = sanitize_filename(filename);
    
    // 2. Generate unique filename (timestamp + original name)
    let unique_name = format!("{}_{}", Utc::now().timestamp(), safe_name);
    
    // 3. Save to disk
    let path = PathBuf::from("forum-rs/static/images").join(&unique_name);
    fs::write(&path, data).await?;
    
    // 4. Return relative path for database
    Ok(format!("public/images/{}", unique_name))
}
```

## Testing

### Unit Tests

```rust
#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_sanitize_filename() {
        assert_eq!(sanitize_filename("test.jpg"), "test.jpg");
        assert_eq!(sanitize_filename("../../../etc/passwd"), "etcpasswd");
    }
    
    #[tokio::test]
    async fn test_create_user() {
        let pool = create_test_db_pool().await;
        
        let user_id = User::create(&pool, "testuser", "hash", "salt", 2)
            .await
            .expect("Failed to create user");
        
        assert!(user_id > 0);
    }
}
```

### Integration Tests

```rust
// tests/integration_test.rs
#[tokio::test]
async fn test_login_flow() {
    let app = create_test_app().await;
    
    // Test GET /user/login
    let response = app
        .get("/user/login")
        .send()
        .await
        .unwrap();
    
    assert_eq!(response.status(), StatusCode::OK);
    
    // Test POST /user/login
    let response = app
        .post("/user/login")
        .form(&[("login", "testuser"), ("password", "password")])
        .send()
        .await
        .unwrap();
    
    assert_eq!(response.status(), StatusCode::FOUND); // Redirect
}
```

## Common Patterns

### Pattern 1: Protect Route with Authentication

```rust
// In main.rs router
.route("/protected", get(protected_handler))

// In handler
pub async fn protected_handler(session: Session) -> impl IntoResponse {
    let user = match session.get::<SessionUser>("user").await.ok().flatten() {
        Some(u) => u,
        None => return Redirect::to("/user/login").into_response(),
    };
    
    // User is authenticated, proceed
}
```

### Pattern 2: Check Resource Ownership

```rust
pub async fn edit_post(
    session: Session,
    State(state): State<AppState>,
    Path(post_id): Path<i64>,
) -> impl IntoResponse {
    let user = require_user(&session).await?;
    
    let post = Post::find_by_id(&state.db, post_id).await?
        .ok_or("Post not found")?;
    
    // Check if user owns this post
    if post.author_id != user.id {
        return Err("Access denied");
    }
    
    // User owns the post, allow editing
}
```

### Pattern 3: Pagination

```rust
pub async fn paginated_list(Query(params): Query<PageParams>) -> impl IntoResponse {
    let page = params.page.unwrap_or(1);
    let per_page = 10;
    let offset = (page - 1) * per_page;
    
    let items = sqlx::query_as(
        "SELECT * FROM items ORDER BY created_at DESC LIMIT ? OFFSET ?"
    )
    .bind(per_page)
    .bind(offset)
    .fetch_all(&pool)
    .await?;
    
    let total = sqlx::query_scalar("SELECT COUNT(*) FROM items")
        .fetch_one(&pool)
        .await?;
    
    let total_pages = (total + per_page - 1) / per_page;
    
    render_template(items, page, total_pages)
}
```

## Deployment Checklist

- [ ] Set strong `SESSION_SECRET` in environment
- [ ] Use production database credentials
- [ ] Enable HTTPS (use reverse proxy like nginx)
- [ ] Set `RUST_ENV=production`
- [ ] Set `RUST_LOG=info` (not debug)
- [ ] Configure database connection pool size
- [ ] Set up database backups
- [ ] Configure file upload size limits
- [ ] Implement rate limiting
- [ ] Add CSRF protection
- [ ] Set up monitoring and logging
- [ ] Configure CORS if needed
- [ ] Use environment variables for secrets (never hardcode)

## Next Steps for Learning

1. **Trace a complete request**: Pick `/posts/{id}` and follow the code from `main.rs` → handler → model → database → template
2. **Add a feature**: Try adding a "like" button to posts
3. **Read the Rust Book**: Chapters on ownership, error handling, and async
4. **Experiment**: Change a template, add a field to a model, create a new endpoint
5. **Ask questions**: Read inline code comments, they explain the "why"

## Useful Resources

- [Axum Examples](https://github.com/tokio-rs/axum/tree/main/examples)
- [SQLx Documentation](https://docs.rs/sqlx/)
- [Sailfish Guide](https://rust-sailfish.github.io/sailfish/)
- [Rust Async Book](https://rust-lang.github.io/async-book/)
