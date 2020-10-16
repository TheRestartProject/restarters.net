# Dockerfile

FROM php:7.4
WORKDIR ./

# We need a later version of node to avoid weird errors.
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt-get update -y && apt-get install -y openssl zip unzip git libxml2-dev libzip-dev zlib1g-dev bash vim procps nodejs

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql xmlrpc zip intl

# Allow debug
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo 'xdebug.remote_port=9000' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_connect_back=1' >> /usr/local/etc/php/php.ini

COPY . /
RUN chmod +x ./compose/scripts/start.sh
RUN mkdir -p /public/uploads

RUN npm install --global cross-env
RUN npm install --quiet

# Command to start things up.
CMD ./compose/scripts/start.sh