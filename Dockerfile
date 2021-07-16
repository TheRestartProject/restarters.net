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

# Copy default env too.
COPY .env.example /var/www/.env

# Grant permissions to /var/www so we can put files in it.
RUN sudo chown -R circleci:circleci /var/www

# Install composer.  Don't run composer install yet - see docker_run.sh
RUN wget https://getcomposer.org/composer-1.phar

# Point at the DB server
RUN sed -i 's/DB_HOST=.*$/DB_HOST=restarters_db/g' /var/www/.env
RUN sed -i 's/DB_DATABASE=.*$/DB_DATABASE=restarters_db_test/g' /var/www/.env

# Turn off session domain, which causes problems in a Docker environment.
RUN sed -i 's/SESSION_DOMAIN=.*$/SESSION_DOMAIN=/g' /var/www/.env

# Change the Discourse host to point at the one defined in docker-compose
RUN sed -i 's/DISCOURSE_URL=.*$/DISCOURSE_URL=http:\/\/restarters_discourse:80/g' /var/www/.env

# Change the database environment used for automated tests.
RUN sed -i 's/SESSION_DOMAIN=.*$/SESSION_DOMAIN=/g' phpunit.xml
RUN sed -i 's/DB_TEST_HOST=.*$/DB_TEST_HOST=restarters_db/g' phpunit.xml

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

CMD ["bash", "docker_run.sh"]