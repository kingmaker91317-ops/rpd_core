FROM php:8.1-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install required system packages and PHP extensions (intl is required by CodeIgniter 4)
RUN apt-get update && apt-get install -y libicu-dev libpng-dev libzip-dev zip unzip && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl pdo pdo_mysql mysqli gd zip

# Copy application files
COPY . /var/www/html/

# Configure Apache DocumentRoot and AllowOverride
ENV APACHE_DOCUMENT_ROOT /var/www/html/public_html/public_html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    echo '<Directory /var/www/html/public_html/public_html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Set permissions for writable directory
RUN chmod -R 777 /var/www/html/public_html/public_html/app/ /var/www/html/public_html/public_html/writable /var/www/html/well-known/writable || true

# Support Render PORT environment variable
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
