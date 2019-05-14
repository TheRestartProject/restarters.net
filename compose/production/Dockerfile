# Dockerfile

FROM php:7
RUN apt-get update -y && apt-get install -y openssl zip unzip git libxml2-dev libzip-dev zlib1g-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql xmlrpc zip
COPY . /app
COPY ./compose/scripts/start.sh /
RUN chmod +x /start.sh
WORKDIR /app
