FROM php:8.4-fpm

# Install dependencies (pdo_sqlite is built into the official image: the demo needs no database server)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    nginx \
    && docker-php-ext-install intl opcache zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Nginx
COPY nginx.conf /etc/nginx/sites-available/default
# Create symlink if it doesn't exist (Debian/Ubuntu default)
# RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Workdir
WORKDIR /srv/app

# Entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
CMD ["/entrypoint.sh"]
