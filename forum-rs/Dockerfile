# Dockerfile for Forum-RS
FROM rust:1.75 as builder

WORKDIR /app

# Copy manifests
COPY Cargo.toml Cargo.lock ./

# Copy source code
COPY src ./src
COPY bin ./bin
COPY templates ./templates

# Build the application in release mode
RUN cargo build --release

# Runtime stage
FROM debian:bookworm-slim

WORKDIR /app

# Install runtime dependencies
RUN apt-get update && apt-get install -y \
    ca-certificates \
    libssl3 \
    && rm -rf /var/lib/apt/lists/*

# Copy the binary from builder
COPY --from=builder /app/target/release/forum /usr/local/bin/forum

# Copy templates and static files
COPY templates ./templates
COPY static ./static

# Create directory for uploaded images
RUN mkdir -p /app/forum-rs/static/images

# Expose port
EXPOSE 3000

# Run the application
CMD ["forum"]
