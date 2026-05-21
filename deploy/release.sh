#!/usr/bin/env bash
# Deploy new code version. Safe to run on every release.
# Usage (from local machine): ssh root@vps "bash /var/www/news/deploy/release.sh"
# Or run directly on the server.
set -euo pipefail

APP_DIR="/var/www/news"
APP_USER="www-data"

cd "$APP_DIR"

echo "==> Pulling latest code"
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true
git pull --ff-only

echo "==> Installing Composer dependencies (no-dev, optimized)"
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

echo "==> Running migrations"
sudo -u "$APP_USER" php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Clearing cache"
sudo -u "$APP_USER" php bin/console cache:clear --env=prod --no-warmup
sudo -u "$APP_USER" php bin/console cache:warmup --env=prod

echo "==> Fixing permissions"
chown -R "$APP_USER:$APP_USER" vendor/ var/

echo "==> Restarting services"
systemctl restart php8.5-fpm
systemctl restart news-consumer
systemctl reload nginx

echo ""
echo "==> Release done."
