#!/bin/bash
# Quick Setup Script for Forum-RS
# This script helps you get started with the Forum application quickly

set -e

echo "=================================="
echo "Forum-RS Quick Setup"
echo "=================================="
echo ""

# Check for Rust
if ! command -v cargo &> /dev/null; then
    echo "❌ Rust is not installed!"
    echo "Please install Rust from: https://rustup.rs/"
    echo "Run: curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh"
    exit 1
fi

echo "✓ Rust is installed ($(rustc --version))"

# Check for MySQL (optional for Docker users)
if command -v mysql &> /dev/null; then
    echo "✓ MySQL is installed"
else
    echo "⚠ MySQL not found - you'll need Docker or install MySQL"
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cp .env.example .env
    echo "✓ Created .env file"
    echo "⚠ Please edit .env with your database credentials"
else
    echo "✓ .env file already exists"
fi

# Create images directory
mkdir -p static/images
touch static/images/.gitkeep
echo "✓ Created static/images directory"

echo ""
echo "=================================="
echo "Setup Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Local Development (requires MySQL):"
echo "   - Edit .env with your database credentials"
echo "   - Create database: mysql -u root -p < ../config/schema.sql"
echo "   - Build: cargo build --release"
echo "   - Run: cargo run --release"
echo "   - Access: http://localhost:3000"
echo ""
echo "2. Docker Deployment (recommended):"
echo "   - Run: docker-compose up -d"
echo "   - Access: http://localhost:3000"
echo "   - View logs: docker-compose logs -f app"
echo ""
echo "3. Development:"
echo "   - Read README.md for full documentation"
echo "   - Read DEVELOPMENT.md for architecture guide"
echo "   - Run tests: cargo test"
echo "   - Format code: cargo fmt"
echo "   - Lint code: cargo clippy"
echo ""
echo "=================================="
