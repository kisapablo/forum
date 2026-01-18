FROM php:8.1-cli-alpine

# Install system dependencies
RUN apk add --no-cache git \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/cache/apk/*

WORKDIR /app

COPY . .

# Install PHP dependencies
RUN php composer.phar install --optimize-autoloader

EXPOSE 8081

CMD ["php", "-S", "0.0.0.0:8081"]
