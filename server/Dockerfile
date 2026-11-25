# Production image for Render (free web service).
FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev libicu-dev libpng-dev unzip ca-certificates \
    && docker-php-ext-install pdo_mysql zip intl bcmath gd opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencies first so they are cached between deploys.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache

# Serve Laravel's public/ directory.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

COPY docker/start.sh /usr/local/bin/start
RUN chmod +x /usr/local/bin/start

CMD ["start"]
