FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev git \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache
RUN a2enmod rewrite

# Set the working directory

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install
# RUN composer install --optimize-autoloader

# Enable Apache rewrite module
RUN a2enmod rewrite

EXPOSE 8081

CMD ["cd /app && php -S 0.0.0.0:8081"]
