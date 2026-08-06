#!/bin/bash
# deploy/deploy.sh
# Deploy/update the app on the VPS.
# Requires: server-setup.sh already run; env vars below or hardcoded.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/oceanovia}"
REPO_URL="${REPO_URL:-https://github.com/SadikMahi213/oceanovia.git}"
BRANCH="${BRANCH:-main}"
APP_USER="${APP_USER:-www-data}"
APP_GROUP="${APP_GROUP:-www-data}"

APP_ENV="${APP_ENV:-production}"
APP_URL="${APP_URL:-http://195.26.245.159}"
APP_KEY="${APP_KEY:-}"   # generated on first deploy if empty
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-oceanovia}"
DB_USERNAME="${DB_USERNAME:-oceanovia}"
DB_PASSWORD="${DB_PASSWORD:-change-me}"
REDIS_HOST="${REDIS_HOST:-127.0.0.1}"
REDIS_PASSWORD="${REDIS_PASSWORD:-null}"
MAIL_MAILER="${MAIL_MAILER:-smtp}"
MAIL_HOST="${MAIL_HOST:-smtp.gmail.com}"
MAIL_PORT="${MAIL_PORT:-587}"
MAIL_USERNAME="${MAIL_USERNAME:-}"
MAIL_PASSWORD="${MAIL_PASSWORD:-}"
STRIPE_KEY="${STRIPE_KEY:-}"
STRIPE_SECRET="${STRIPE_SECRET:-}"
STRIPE_WEBHOOK_SECRET="${STRIPE_WEBHOOK_SECRET:-}"
SESSION_DRIVER="${SESSION_DRIVER:-redis}"
CACHE_STORE="${CACHE_STORE:-redis}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"

echo "=== [1/9] Fetching code ==="
if [ ! -d "$APP_DIR" ]; then
    mkdir -p "$APP_DIR"
    git clone --depth 1 -b "$BRANCH" "$REPO_URL" "$APP_DIR"
else
    cd "$APP_DIR"
    git fetch origin "$BRANCH"
    git checkout "$BRANCH"
    git reset --hard origin/"$BRANCH"
    cd -
fi

echo "=== [2/9] Writing .env ==="
if [ -z "$APP_KEY" ]; then
    APP_KEY="base64:$(openssl rand -base64 32)"
fi

cat > "$APP_DIR/.env" <<EOF
APP_NAME=Oceanovia
APP_ENV=$APP_ENV
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=$APP_URL
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_DATABASE=$DB_DATABASE
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=$SESSION_DRIVER
SESSION_LIFETIME=120
CACHE_STORE=$CACHE_STORE
QUEUE_CONNECTION=$QUEUE_CONNECTION
REDIS_HOST=$REDIS_HOST
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_PORT=6379
REDIS_CLIENT=phpredis

MAIL_MAILER=$MAIL_MAILER
MAIL_HOST=$MAIL_HOST
MAIL_PORT=$MAIL_PORT
MAIL_USERNAME=$MAIL_USERNAME
MAIL_PASSWORD=$MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@oceanovia.app"
MAIL_FROM_NAME="Oceanovia"

STRIPE_KEY=$STRIPE_KEY
STRIPE_SECRET=$STRIPE_SECRET
STRIPE_WEBHOOK_SECRET=$STRIPE_WEBHOOK_SECRET

FILESYSTEM_DISK=local
SANCTUM_STATEFUL_DOMAINS=195.26.245.159
TRUSTED_PROXIES=
EOF

echo "=== [3/9] Installing composer deps ==="
cd "$APP_DIR"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "=== [4/9] Building frontend ==="
npm ci --no-audit --no-fund
npm run build

echo "=== [5/9] Creating DB + user ==="
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "=== [6/9] Migrations + seed ==="
php artisan migrate --force
php artisan db:seed --force

echo "=== [7/9] Caching ==="
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

echo "=== [8/9] Permissions ==="
chown -R "$APP_USER:$APP_GROUP" "$APP_DIR"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "=== [9/9] Reloading services ==="
systemctl reload php8.4-fpm
systemctl reload nginx
supervisorctl reread 2>/dev/null || true
supervisorctl update 2>/dev/null || true

echo ""
echo "=== Deploy complete: $APP_URL ==="
