FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
COPY tools/copy-assets.mjs tools/copy-assets.mjs
RUN npm ci --omit=dev --no-audit --no-fund && npm run build

FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --ignore-platform-req=ext-gd --ignore-platform-req=ext-pdo_pgsql

FROM php:8.3-cli-alpine
RUN apk add --no-cache libpq libpng libjpeg-turbo libwebp freetype icu-libs oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev icu-dev oniguruma-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j2 pdo_pgsql gd intl mbstring \
    && apk del .build-deps
WORKDIR /app
COPY . .
COPY docker/php.ini /usr/local/etc/php/conf.d/huellas.ini
COPY --from=dependencies /app/vendor vendor
COPY --from=assets /app/public/vendor public/vendor
COPY .env.example .env
RUN mkdir -p var/uploads var/cache var/log && chown -R www-data:www-data var
ENV APP_ENV=prod APP_DEBUG=0
USER www-data
EXPOSE 8000
CMD ["sh", "-c", "php bin/console doctrine:migrations:migrate --no-interaction && php -S 0.0.0.0:8000 -t public public/router.php"]
