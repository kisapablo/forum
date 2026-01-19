#!/bin/bash
echo "Testing if templates compile..."
cd forum-rs
cargo check 2>&1 | head -50
