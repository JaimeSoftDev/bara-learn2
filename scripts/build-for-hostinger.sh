#!/usr/bin/env bash
#
# Builds the Vue SPA and copies the compiled output into the Laravel
# public/ folder, so the whole app can be deployed to shared hosting
# (no Node.js required on the server — see docs/deploy-hostinger.md).
#
# Run this locally (or in CI) before committing/pushing, then on the
# server just `git pull` + `composer install` are needed.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND_DIR="$ROOT_DIR/frontend"
PUBLIC_DIR="$ROOT_DIR/backend/public"

echo "==> Installing frontend dependencies"
cd "$FRONTEND_DIR"
npm ci

echo "==> Building the SPA for production (relative /api paths)"
# Intentionally unset so the app calls the API on its own origin in
# production (see src/api/client.ts). Local dev keeps using frontend/.env.
unset VITE_API_URL
npm run build

echo "==> Copying build output into backend/public"
rm -rf "$PUBLIC_DIR/assets"
cp -R "$FRONTEND_DIR/dist/assets" "$PUBLIC_DIR/assets"
cp "$FRONTEND_DIR/dist/index.html" "$PUBLIC_DIR/spa-index.html"
if [ -f "$FRONTEND_DIR/dist/favicon.ico" ]; then
  cp "$FRONTEND_DIR/dist/favicon.ico" "$PUBLIC_DIR/favicon.ico"
fi

echo "==> Done. Commit backend/public/assets and backend/public/spa-index.html."
