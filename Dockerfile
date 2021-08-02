# This is the docker for restarters.  It's used from docker-compose.
FROM circleci/php:7.4-node-browsers

# Set working directory to where we will run.
WORKDIR /var/www

# Install dependencies
RUN sudo apt-get update && \
    sudo apt-get install build-essential locales curl unzip openssl zip unzip git libxml2-dev libzip-dev zlib1g-dev libcurl4-openssl-dev iputils-ping default-mysql-client vim libpng-dev libgmp-dev libjpeg62-turbo-dev

# Clear cache - reduces image size.
RUN sudo apt-get clean && sudo rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN sudo docker-php-ext-configure gd --with-jpeg
RUN sudo docker-php-ext-install zip pdo pdo_mysql xmlrpc curl gd zip

# Copy the code
COPY . /var/www/

# Copy composer.lock and composer.json from the codebase to where we will install and run.
COPY composer.lock composer.json /var/www/

# Grant permissions to /var/www so we can put files in it.
RUN sudo chown -R circleci:circleci /var/www

# Install composer.  Don't run composer install yet - see docker_run.sh
RUN wget https://getcomposer.org/composer-1.phar

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

CMD ["bash", "docker_run.sh"]