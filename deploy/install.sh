#!/usr/bin/env bash
# One-time server installation. Run as root on a fresh Ubuntu 24.04 / Debian 12.
set -euo pipefail

APP_USER="www-data"
APP_DIR="/var/www/news"

echo "==> Updating packages"
apt-get update -y

echo "==> Adding ondrej/php PPA (PHP 8.5)"
apt-get install -y software-properties-common ca-certificates curl gnupg lsb-release
LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
apt-get update -y

echo "==> Installing PHP 8.5 + extensions"
apt-get install -y \
  php8.5-fpm php8.5-cli php8.5-mysql php8.5-xml php8.5-curl \
  php8.5-mbstring php8.5-intl php8.5-zip php8.5-amqp

echo "==> Installing Nginx"
apt-get install -y nginx

echo "==> Installing MySQL 8"
apt-get install -y mysql-server

echo "==> Installing RabbitMQ"
apt-get install -y rabbitmq-server
systemctl enable rabbitmq-server
systemctl start rabbitmq-server

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Installing tools"
apt-get install -y git unzip certbot python3-certbot-nginx

echo "==> Adding swap (1GB) for low-memory VPS"
if ! swapon --show | grep -q swap; then
    fallocate -l 1G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    sysctl vm.swappiness=10
    echo 'vm.swappiness=10' >> /etc/sysctl.conf
    echo "   swap created"
else
    echo "   swap already present, skipping"
fi

echo "==> Creating app directory"
mkdir -p "$APP_DIR"
chown "$APP_USER:$APP_USER" "$APP_DIR"

echo "==> MySQL: creating database and user"
echo "Enter MySQL root password (or press Enter if auth_socket is used):"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS news CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'news'@'localhost' IDENTIFIED BY 'change_me_in_env';
GRANT ALL PRIVILEGES ON news.* TO 'news'@'localhost';
FLUSH PRIVILEGES;
SQL

echo ""
echo "==> Install complete. Next: bash configure.sh"
