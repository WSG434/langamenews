#!/usr/bin/env bash
# Server configuration: nginx, php-fpm pools, systemd, cron.
# Run as root after install.sh and after cloning the repo to /var/www/news.
set -euo pipefail

APP_DIR="/var/www/news"
DOMAIN="${1:-localhost}"

echo "==> Configuring Nginx (domain: ${DOMAIN})"
cp "${APP_DIR}/deploy/nginx/news.conf" /etc/nginx/sites-available/news
sed -i "s/server_name _;/server_name ${DOMAIN};/" /etc/nginx/sites-available/news
ln -sf /etc/nginx/sites-available/news /etc/nginx/sites-enabled/news
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

echo "==> Configuring PHP-FPM pools"
cp "${APP_DIR}/deploy/php-fpm/news.conf"        /etc/php/8.5/fpm/pool.d/news.conf
cp "${APP_DIR}/deploy/php-fpm/news_stream.conf" /etc/php/8.5/fpm/pool.d/news_stream.conf
# Remove default www pool
rm -f /etc/php/8.5/fpm/pool.d/www.conf
systemctl restart php8.5-fpm

echo "==> Installing systemd units"
cp "${APP_DIR}/deploy/systemd/news-consumer.service" /etc/systemd/system/news-consumer.service
cp "${APP_DIR}/deploy/systemd/news-telegram.service" /etc/systemd/system/news-telegram.service
systemctl daemon-reload
systemctl enable news-consumer
systemctl enable news-telegram

echo "==> Installing cron job (news import every 10 min)"
cp "${APP_DIR}/deploy/cron/news-import" /etc/cron.d/news-import
chmod 644 /etc/cron.d/news-import

echo ""
echo "==> Configure complete. Now run: bash release.sh"
echo "    Then: php ${APP_DIR}/bin/console app:setup --env=prod"
echo "    Then: php ${APP_DIR}/bin/console app:user:promote your@email.com --env=prod"
echo "    Then (HTTPS): certbot --nginx -d ${DOMAIN}"
