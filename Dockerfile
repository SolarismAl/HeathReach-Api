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

# Optimize Laravel (don’t run key:generate here, Render provides APP_KEY)
RUN php artisan config:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Point Apache document root to Laravel's public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/|/var/www/html/public|g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 8080 (Apache default inside container)
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2-foreground"]
