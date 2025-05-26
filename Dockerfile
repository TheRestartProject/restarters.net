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
    bcmath \
    zip \
    xmlrpc \
    xdebug \
    intl \
    gd

# Install composer.  Don't run composer install yet - see docker_run.sh
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

RUN git config --system --add safe.directory /var/www

# Set working directory to where we will run.
WORKDIR /var/www

# Allow a GID variable to be set from the command line.
ARG GID=1000

# Use host's group IDs for www-data.
RUN groupmod -g ${GID} www-data && \
    # Set www-data's home directory to /home/www-data and ensure it exists.
    # This is needed to ensure that commands like composer install do
    # not assume that the home directory is /var/www - i.e. the working directory.
    mkdir -p /home/www-data && \
    usermod -d /home/www-data www-data && \
    usermod -a -G ${GID} www-data && \
    # Ensure the home directory has correct permissions
    chown www-data:${GID} /home/www-data

USER www-data

# Copy the code
COPY . ./

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

RUN pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 xmlrpc

CMD ["bash", "docker_run.sh"]