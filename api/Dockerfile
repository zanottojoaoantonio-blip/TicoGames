FROM dunglas/frankenphp:1-php8.4-trixie

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql zip bcmath intl pcntl pdo_sqlite \
    && apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && git config --system --add safe.directory /app \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs
COPY Caddyfile /etc/frankenphp/Caddyfile

EXPOSE 8000

CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && composer check-platform-reqs && exec frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile"]
