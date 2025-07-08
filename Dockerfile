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
        mysql-client-core-8.0 \
        postgresql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install Playwright system dependencies
# We need to install @playwright/test first to get the install-deps command
RUN npm install -g @playwright/test && \
    npx playwright install-deps && \
    npm uninstall -g @playwright/test

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.7.31/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    zip \
    xmlrpc \
    xdebug \
    intl \
    exif \
    pcntl \
    gd

# Install composer.  Don't run composer install yet - see docker_run.sh
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

RUN git config --system --add safe.directory /var/www

# Set working directory to where we will run.
WORKDIR /var/www

# Add ARGs for syncing permissions
ARG UID=1000
ARG GID=1000

# Create a new user with the specified UID and GID, reusing an existing group if GID exists
RUN if getent group ${GID}; then \
      group_name=$(getent group ${GID} | cut -d: -f1); \
      useradd -m -u ${UID} -g ${GID} -s /bin/bash restarter; \
    else \
      groupadd -g ${GID} restarter && \
      useradd -m -u ${UID} -g restarter -s /bin/bash restarter; \
    fi

# Dynamically update php-fpm to use the new user and group
RUN sed -i "s/user = www-data/user = restarter/g" /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/group = www-data/group = restarter/g" /usr/local/etc/php-fpm.d/www.conf

# Copy the code (this will be overridden by the volume mount in docker-compose)
COPY --chown=${UID}:${GID} . ./

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

# Install xmlrpc as the restarter user to avoid permission issues
USER root
RUN pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3 xmlrpc
USER restarter

CMD ["bash", "docker_run.sh"]