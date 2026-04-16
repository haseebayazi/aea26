#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# AEA26 Portal — cPanel Deployment Script
# Run once after uploading files to the server.
#
# USAGE:
#   1. Upload the entire portal/ directory to ~/aea26-portal/
#   2. SSH into cPanel and run: bash ~/aea26-portal/deploy.sh
# ─────────────────────────────────────────────────────────────────────────────
set -e

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "Deploying from: $APP_DIR"

# ── 1. Install PHP dependencies ───────────────────────────────────────────────
echo ""
echo "→ Installing Composer dependencies (no-dev)…"
composer install --no-dev --optimize-autoloader --no-interaction

# ── 2. Environment file ───────────────────────────────────────────────────────
if [ ! -f "$APP_DIR/.env" ]; then
    echo ""
    echo "→ Copying .env.example to .env…"
    cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    echo "  ⚠  EDIT $APP_DIR/.env now — set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL"
    echo "  Then re-run this script."
    exit 1
fi

# ── 3. Generate app key (only if not set) ────────────────────────────────────
if grep -q "APP_KEY=$" "$APP_DIR/.env" || grep -q "APP_KEY=base64:$" "$APP_DIR/.env"; then
    echo ""
    echo "→ Generating app key…"
    php artisan key:generate --force
fi

# ── 4. Storage symlink ────────────────────────────────────────────────────────
echo ""
echo "→ Creating storage symlink…"
php artisan storage:link --force

# ── 5. Run migrations ─────────────────────────────────────────────────────────
echo ""
echo "→ Running database migrations…"
php artisan migrate --force

# ── 6. Seed database ─────────────────────────────────────────────────────────
read -p "Seed database? This imports students + creates admin user. (y/N) " SEED
if [[ "$SEED" =~ ^[Yy]$ ]]; then
    echo "→ Seeding database…"
    echo "  NOTE: xlsx data files must be at $APP_DIR/../ (parent directory)"
    php artisan db:seed --force
fi

# ── 7. Cache for production ───────────────────────────────────────────────────
echo ""
echo "→ Caching config, routes, views…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 8. File permissions ───────────────────────────────────────────────────────
echo ""
echo "→ Setting permissions…"
chmod -R 775 storage bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find storage -type d -exec chmod 775 {} \;

# ── 9. Symlink public_html ────────────────────────────────────────────────────
echo ""
echo "To serve from public_html, run ONE of:"
echo "  ln -sfn $APP_DIR/public ~/public_html/alumni"
echo "  (for subdirectory: ~/public_html/alumni/ → portal/public/)"
echo ""
echo "  OR for subdomain (alumni.yoursite.com):"
echo "  ln -sfn $APP_DIR/public ~/public_html"

echo ""
echo "✓ Deployment complete!"
echo "  Admin login: admin@alumni-awards.com"
echo "  ⚠  CHANGE THE DEFAULT PASSWORD immediately after first login (Admin → Users → Reset Password)."
