FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev git \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

# Install PHP dependencies
RUN php composer.phar install --optimize-autoloader

EXPOSE 8081

CMD ["php", "index.php"]
