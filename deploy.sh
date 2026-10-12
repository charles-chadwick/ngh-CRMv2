#!/usr/bin/env bash
#
# Deploy script for nghcrm.net, invoked by GitHub Actions over SSH on push to main.
# Runs as the `deploy` user. Any failing step aborts the deploy.
#
set -euo pipefail

APP_DIR=/var/www/nghcrm.net
cd "$APP_DIR"

echo "==> Checking that this deploy can replace every file it owns"
# composer, npm, and git all replace files in place, which needs write access on
# the *containing* directory, not just the file. A stray `sudo composer install`
# leaves root-owned trees behind, and the failure then surfaces deep inside
# Composer as a bare "Could not delete ..." with no hint at the cause. Catch it
# here, before maintenance mode, so a failed check costs no downtime.
#
# storage and bootstrap/cache are deliberately exempt: the queue worker and
# Reverb run as www-data and legitimately create files in them, which is why
# those two trees are group-writable and setgid (see "Normalising permissions").
deploy_user=$(id -un)
foreign_paths=$(find . \
    -path ./storage -prune -o \
    -path ./bootstrap/cache -prune -o \
    ! -user "$deploy_user" -print)

if [[ -n "$foreign_paths" ]]; then
    echo "ERROR: $(printf '%s\n' "$foreign_paths" | wc -l) path(s) in $APP_DIR are not owned by $deploy_user," >&2
    echo "so this deploy cannot replace them. Usually caused by running composer," >&2
    echo "npm, or artisan as root here. First 10:" >&2
    # sed rather than `head -10`, which would close the pipe early and kill
    # printf with SIGPIPE -- under pipefail that aborts before the fix below.
    printf '%s\n' "$foreign_paths" | sed -n '1,10{s/^/  /;p}' >&2
    echo >&2
    echo "Fix as root on the server, then re-run the deploy:" >&2
    echo "  find $APP_DIR ! -user $deploy_user -print0 | xargs -0 -r chown $deploy_user:www-data" >&2
    exit 1
fi

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
