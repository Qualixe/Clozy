# =============================================================================
# Clozy (Bagisto) — Render Web Service image
#
# Builds FROM THIS REPOSITORY'S SOURCE (unlike docker/production/, which
# clones upstream Bagisto). Multi-stage: Composer deps, Vite asset builds
# for Admin/Shop/Installer, then a single runtime container (Nginx +
# PHP-FPM 8.3 + Supervisor) listening on Render's injected $PORT.
#
# MySQL is NOT bundled — this service expects DB_* env vars pointing at a
# separate MySQL service (see render.yaml).
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Composer dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
COPY app ./app
COPY database ./database
COPY packages ./packages

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
    && rm -rf /root/.composer/cache


# -----------------------------------------------------------------------------
# Stage 2: Frontend asset builds (Admin, Shop, Installer)
# -----------------------------------------------------------------------------
FROM node:20-slim AS node-assets

WORKDIR /app
COPY . .

RUN cd packages/Webkul/Admin && npm install && npm run build \
    && cd /app/packages/Webkul/Shop && npm install && npm run build \
    && cd /app/packages/Webkul/Installer && npm install && npm run build


# -----------------------------------------------------------------------------
# Stage 3: Runtime
# -----------------------------------------------------------------------------
FROM ubuntu:24.04 AS runtime

ARG PHP_VERSION=8.3

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

# ---------------------------------------------------------------------------
# System packages + PHP PPA
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gettext-base \
        gnupg \
        software-properties-common \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y \
        imagemagick \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP 8.3 + extensions (matches composer.json + Bagisto requirements)
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-calendar \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-exif \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-gmp \
        php${PHP_VERSION}-imagick \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pdo \
        php${PHP_VERSION}-soap \
        php${PHP_VERSION}-sockets \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Application source
# ---------------------------------------------------------------------------
COPY . .
COPY --from=composer-deps /app/vendor ./vendor
COPY --from=node-assets /app/public/themes ./public/themes

# Minimal .env — real values come from Render's runtime env vars, which
# phpdotenv does not override (already-set env vars win). This file only
# exists so Laravel's env loader has something to safely no-op against.
RUN cp .env.example .env

# ---------------------------------------------------------------------------
# PHP / Nginx / Supervisor configuration
# ---------------------------------------------------------------------------
COPY docker/render/config/php.ini /etc/php/${PHP_VERSION}/fpm/conf.d/99-production.ini
COPY docker/render/config/php-fpm.conf /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
COPY docker/render/config/supervisord.conf /etc/supervisor/conf.d/clozy.conf

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf
RUN mkdir -p /etc/nginx/templates
COPY docker/render/config/nginx.conf.template /etc/nginx/templates/nginx.conf.template

# ---------------------------------------------------------------------------
# Runtime directories + permissions
# ---------------------------------------------------------------------------
RUN mkdir -p /run/php /var/log/supervisor /var/log/bagisto \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && find storage bootstrap/cache -type d -exec chmod g+s {} +

# ---------------------------------------------------------------------------
# Entrypoint
# ---------------------------------------------------------------------------
COPY docker/render/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ---------------------------------------------------------------------------
# Cleanup
# ---------------------------------------------------------------------------
RUN apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
