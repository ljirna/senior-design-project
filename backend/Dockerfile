FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    mariadb-client \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite for routing
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Configure Apache
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/app.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/conf-available/app.conf && \
    echo '    AllowOverride All' >> /etc/apache2/conf-available/app.conf && \
    echo '    Require all granted' >> /etc/apache2/conf-available/app.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/app.conf && \
    a2enconf app

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
