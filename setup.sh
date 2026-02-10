#!/usr/bin/env bash

set -e

EXAMPLE_FILE=".env.docker.example"
ENV_FILE=".env.docker"

if [ ! -f "$EXAMPLE_FILE" ]; then
  echo "❌ $EXAMPLE_FILE not found"
  exit 1
fi

if [ -f "$ENV_FILE" ]; then
  read -p "⚠️  $ENV_FILE already exists. Overwrite? (y/N): " confirm
  [[ "$confirm" =~ ^[Yy]$ ]] || exit 0
fi

cp "$EXAMPLE_FILE" "$ENV_FILE"

# Get APP_URL
DEFAULT_URL="http://localhost:8088"
read -p "Enter APP_URL [$DEFAULT_URL]: " APP_URL
APP_URL=${APP_URL:-$DEFAULT_URL}

# Generate APP_KEY
APP_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")

# Replace values
sed -i.bak "s|^APP_URL=.*|APP_URL=${APP_URL}|" "$ENV_FILE"
sed -i.bak "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" "$ENV_FILE"

rm -f "$ENV_FILE.bak"

echo ""
echo "✅ .env.docker created successfully"
echo "APP_URL=$APP_URL"
echo "APP_KEY=$APP_KEY"
