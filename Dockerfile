FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

# Install gRPC (required by google/cloud-firestore)
RUN pecl install grpc \
    && docker-php-ext-enable grpc

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy app files
COPY . .

# Install dependencies (only production deps)
RUN composer install --no-dev --optimize-autoloader

# Don’t run key:generate here (Render provides APP_KEY in env)
# Only cache config/routes/views
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port (Render maps PORT env automatically)
EXPOSE 10000

# Start Laravel server (use Render's PORT)
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
