#!/bin/bash
# deploy/server-setup.sh
# One-time provisioning for a fresh Ubuntu 24.04 LTS VPS (root).
# Run as root:  bash server-setup.sh
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

echo "=== [1/8] Updating system ==="
apt-get update -y
apt-get upgrade -y

echo "=== [2/8] Installing base packages ==="
apt-get install -y \
    software-properties-common apt-transport-https curl wget git unzip zip \
    nginx mysql-server redis-server supervisor fail2ban ufw \
    certbot python3-certbot-nginx prometheus prometheus-node-exporter \
    ca-certificates gnupg lsb-release

echo "=== [3/8] Installing PHP 8.3 + extensions ==="
add-apt-repository ppa:ondrej/php -y
apt-get update -y
apt-get install -y \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis \
    php8.3-bcmath php8.3-gd php8.3-imagick php8.3-xml php8.3-mbstring \
    php8.3-curl php8.3-zip php8.3-intl php8.3-sqlite3 \
    php8.3-bz2 php8.3-opcache

echo "=== [4/8] Installing Composer ==="
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "=== [5/8] Installing Node.js 22 + npm ==="
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs

echo "=== [6/8] Enabling services ==="
systemctl enable --now nginx
systemctl enable --now php8.3-fpm
systemctl enable --now mysql
systemctl enable --now redis-server
systemctl enable --now supervisor

echo "=== [7/8] Configuring firewall (22/80/443) ==="
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "=== [8/8] Configuring fail2ban ==="
if [ ! -f /etc/fail2ban/jail.local ]; then
    cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
fi
systemctl enable --now fail2ban

echo ""
echo "=== Server setup complete ==="
echo "PHP:    $(php -v | head -1)"
echo "MySQL:  $(mysql --version)"
echo "Redis:  $(redis-server --version | head -1)"
echo "Nginx:  $(nginx -v 2>&1)"
echo "Node:   $(node -v)"
echo "Composer: $(composer --version)"
