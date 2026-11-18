FROM php:8.2-apache

# Install system dependencies, PostgreSQL dev libraries, and Python 3
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    python3 \
    python3-pip \
    python-is-python3 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required for Laravel & Supabase (PostgreSQL)
RUN docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Python packages required for waybill parsing and PDF merging
RUN pip3 install --no-cache-dir --break-system-packages PyPDF2 reportlab

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache DocumentRoot to Laravel /public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html

# Copy Project Files
COPY . /var/www/html

# Copy and prepare entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i -e 's/\r$//' /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

# Install Composer PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set storage & cache permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose default port
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
