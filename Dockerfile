# Uhai Bora web server: PHP 8.3 with Apache.
# The code also runs on PHP 7.3+ (for example older XAMPP installs).
FROM php:8.3-apache

# PHP extension and Apache modules used by the app.
RUN docker-php-ext-install mysqli \
    && a2enmod rewrite headers expires deflate

# Production PHP defaults plus Uhai Bora's overrides (errors are logged, never shown).
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/uhai-bora.ini "$PHP_INI_DIR/conf.d/zz-uhai-bora.ini"
COPY docker/apache/uhai-bora.conf /etc/apache2/conf-available/uhai-bora.conf
RUN a2enconf uhai-bora

ENV APP_ENV=production

COPY --chown=www-data:www-data . /var/www/html/
