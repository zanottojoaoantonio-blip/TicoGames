FROM dunglas/frankenphp:1-php8.4-trixie

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql zip bcmath intl opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY . .

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && composer check-platform-reqs --no-dev

EXPOSE 8000

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
