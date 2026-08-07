#!/usr/bin/env bash
#
# Deploy script for nghcrm.net, invoked by GitHub Actions over SSH on push to main.
# Runs as the `deploy` user. Any failing step aborts the deploy.
#
set -euo pipefail

APP_DIR=/var/www/nghcrm.net
cd "$APP_DIR"

echo "==> Putting application into maintenance mode"
php artisan down --render="errors::503" --retry=60 || true
# Always lift maintenance mode, even if a step below fails.
trap 'php artisan up || true' EXIT

echo "==> Fetching latest main"
git fetch --prune origin main
git reset --hard origin/main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Building front-end assets"
npm ci --no-audit --no-fund
npm run build

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "==> Normalising permissions"
chmod -R 2775 storage bootstrap/cache

# The queue worker and Reverb hold code in memory, so they must be restarted to
# pick up this release. Permitted without a password via /etc/sudoers.d/nghcrm-deploy.
echo "==> Restarting background services"
sudo -n systemctl restart nghcrm-queue
sudo -n systemctl restart nghcrm-reverb

echo "==> Deploy complete: $(git log --oneline -1)"
