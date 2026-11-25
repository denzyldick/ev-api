FROM dunglas/frankenphp:1.14.0-php8.5

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    pdo_sqlite \
    zip

WORKDIR /app

COPY . /app

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 8000

ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
