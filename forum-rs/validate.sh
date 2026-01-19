#!/bin/bash
# Validation script to check for common issues

echo "=== Forum-RS Code Validation ==="
echo ""

# Check for old sailfish::render! macro usage
echo "Checking for old sailfish::render! macro usage..."
if grep -r "sailfish::render!" forum-rs/src/ 2>/dev/null; then
    echo "❌ Found old sailfish::render! macro usage"
    exit 1
else
    echo "✓ No sailfish::render! macros found"
fi

# Check for TemplateOnce imports
echo ""
echo "Checking for TemplateOnce imports in handlers..."
for file in forum-rs/src/handlers/*.rs; do
    if [[ $(basename "$file") != "mod.rs" ]]; then
        if grep -q "use sailfish::TemplateOnce" "$file"; then
            echo "✓ $(basename $file) has TemplateOnce import"
        else
            echo "❌ $(basename $file) missing TemplateOnce import"
            exit 1
        fi
    fi
done

# Check for render_once() usage
echo ""
echo "Checking for render_once() method usage..."
if grep -r "render_once()" forum-rs/src/handlers/ >/dev/null 2>&1; then
    echo "✓ Found render_once() method calls"
else
    echo "❌ No render_once() calls found"
    exit 1
fi

# Check project structure
echo ""
echo "Checking project structure..."
required_files=(
    "forum-rs/Cargo.toml"
    "forum-rs/README.md"
    "forum-rs/DEVELOPMENT.md"
    "forum-rs/docker-compose.yml"
    "forum-rs/Dockerfile"
    "forum-rs/bin/main.rs"
    "forum-rs/src/lib.rs"
)

for file in "${required_files[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file exists"
    else
        echo "❌ $file missing"
        exit 1
    fi
done

# Check for key directories
required_dirs=(
    "forum-rs/src/handlers"
    "forum-rs/src/models"
    "forum-rs/src/templates"
    "forum-rs/src/middleware"
    "forum-rs/src/utils"
    "forum-rs/templates"
    "forum-rs/static"
)

for dir in "${required_dirs[@]}"; do
    if [ -d "$dir" ]; then
        echo "✓ $dir exists"
    else
        echo "❌ $dir missing"
        exit 1
    fi
done

echo ""
echo "=== All Validation Checks Passed! ==="
echo ""
echo "Project Statistics:"
echo "- Rust source files: $(find forum-rs/src forum-rs/bin -name '*.rs' | wc -l)"
echo "- Template files: $(find forum-rs/templates -name '*.stpl' 2>/dev/null | wc -l)"
echo "- render_once() calls: $(grep -r "render_once()" forum-rs/src/handlers/ | wc -l)"
echo ""
