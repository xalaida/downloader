# Image
FROM php:7.1-cli

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
    && docker-php-ext-enable xdebug \
# Clean up the apt cache
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set up working directory
WORKDIR /app
