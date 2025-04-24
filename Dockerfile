# This is the docker for restarters.  It's used from docker-compose.
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        git \
        zip \
        unzip \
        npm \
        vim \
        default-mysql-client \
        postgresql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.31/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    pcntl \
    bcmath \
    zip \
    xmlrpc \
    xdebug \
    intl \
    gd

# Install composer.  Don't run composer install yet - see docker_run.sh
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Set working directory to where we will run.
WORKDIR /var/www

# Copy the code
COPY . ./

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

RUN pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 xmlrpc

CMD ["bash", "docker_run.sh"]