#!/bin/bash
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

log() {
    echo "[clozy-entrypoint] $(date '+%Y-%m-%d %H:%M:%S') $*"
}

# ==========================================================================
# Render injects PORT at runtime — the port this container must listen on
# is not fixed at build time. Template it into the nginx server block.
# ==========================================================================
export PORT="${PORT:-10000}"
log "Binding nginx to port ${PORT}"
envsubst '${PORT}' < /etc/nginx/templates/nginx.conf.template > /etc/nginx/conf.d/clozy.conf

# ==========================================================================
# Wait for the database to be reachable before caching config / starting.
# Render's private services can come up in any order on first deploy.
# ==========================================================================
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-forge}"
DB_PASSWORD="${DB_PASSWORD:-}"

log "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
    if php -r "try { new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}'); exit(0); } catch (\Throwable \$e) { exit(1); }" 2>/dev/null; then
        log "MySQL is reachable."
        break
    fi
    if [ "$i" -eq 60 ]; then
        log "ERROR: Cannot reach MySQL at ${DB_HOST}:${DB_PORT} after 60s"
        exit 1
    fi
    sleep 1
done

# ==========================================================================
# Ephemeral runtime directories (not covered by the persistent disk, which
# is mounted at storage/app/public only) — recreate defensively on boot.
# ==========================================================================
mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ==========================================================================
# public/storage is a symlink into the persistent-disk-backed
# storage/app/public. The container filesystem is rebuilt on every deploy,
# so the symlink itself must be recreated on every boot.
# ==========================================================================
if [ ! -L public/storage ]; then
    log "Creating storage symlink..."
    php artisan storage:link --force
fi

# ==========================================================================
# Cache config/routes/views using the REAL runtime environment (Render's
# dashboard-configured env vars). Must happen at boot, not at build time —
# config:cache freezes whatever env values are present when it runs.
# ==========================================================================
log "Caching configuration..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ==========================================================================
# Optional: run migrations on boot instead of via Render's Pre-Deploy
# Command. Off by default — prefer the Pre-Deploy Command hook so
# migrations run once per deploy, not once per container start.
# ==========================================================================
if [ "${RUN_MIGRATIONS_ON_BOOT:-false}" = "true" ]; then
    log "RUN_MIGRATIONS_ON_BOOT=true — running migrations..."
    php artisan migrate --force
fi

log "Starting services via Supervisor..."
exec "$@"
