# Image de production de Parole tenue : PHP 8.4 (FPM) + Nginx, port 8080, utilisateur www-data.
# Construite par GitHub Actions (.github/workflows/deploy.yml), déployée sur le VPS derrière Traefik.
# La même image sert l'application, le worker Horizon et le planificateur (deploy/docker-compose.yml).

# --- Assets (Vite + Tailwind) ---
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
# Tailwind analyse les vues et les classes PHP (app/View/Appearance.php…) : tout le code est utile.
COPY . .
RUN npm run build

# --- Application ---
FROM serversideup/php:8.4-fpm-nginx-alpine AS app

USER root
# intl : exigé par Filament ; pdo_pgsql, redis, pcntl (Horizon) sont déjà dans l'image.
RUN install-php-extensions intl
USER www-data

WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Au démarrage du conteneur « app » (AUTORUN_ENABLED) : migrations, lien de stockage,
# caches de configuration, routes et vues. Le worker et le planificateur le désactivent.
# Commit de construction, affiché à côté de la version (passé par le workflow).
ARG APP_COMMIT=""
ENV APP_COMMIT=${APP_COMMIT} \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION=true \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off
