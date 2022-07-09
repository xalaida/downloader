# Image
FROM php:7.0-cli

# Update dependencies
RUN apt-get update \
# Install Curl
    && apt-get install -y libcurl3-dev curl \
    && docker-php-ext-install curl \
# Install Zip
    && apt-get install -y libzip-dev zip \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install zip \
# Install Xdebug
    && pecl install xdebug-2.5.0 \
    && docker-php-ext-enable xdebug

# Install composer
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
RUN chmod 0755 /usr/bin/composer

# Set up default directory
WORKDIR /app
