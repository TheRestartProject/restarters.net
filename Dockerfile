# This is the docker for restarters.  It's used from docker-compose.
FROM php:8.2-fpm

# Set working directory to where we will run.
WORKDIR /var/www

# Install dependencies
RUN sudo apt-get update && \
    sudo apt install dnsutils openssl zip unzip git libxml2-dev libzip-dev zlib1g-dev libcurl4-openssl-dev iputils-ping default-mysql-client vim libpng-dev libgmp-dev libjpeg-turbo8-dev && \
    sudo apt install php8.1-xmlrpc php8.1-intl php8.1-xdebug php8.1-xmlrpc php8.1-mbstring php8.1-simplexml php8.1-curl php8.1-zip python2 postgresql-client procps telnet vim openssh-server php-xmlrpc

# Clear cache - reduces image size.
RUN sudo apt-get clean && sudo rm -rf /var/lib/apt/lists/*

# Copy the code
COPY . /var/www/

# Copy composer.lock and composer.json from the codebase to where we will install and run.
COPY composer.lock composer.json /var/www/

# Install composer.  Don't run composer install yet - see docker_run.sh
RUN wget https://getcomposer.org/composer-1.phar

# Expose port 9000, which is our PHP FPM port referenced from nginx.conf.
EXPOSE 9000

# Install sshd
RUN sudo sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
RUN sudo mkdir /var/run/sshd
RUN sudo bash -c 'install -m755 <(printf "#!/bin/sh\nexit 0") /usr/sbin/policy-rc.d'
RUN sudo ex +'%s/^#\zeListenAddress/\1/g' -scwq /etc/ssh/sshd_config
RUN sudo ex +'%s/^#\zeHostKey .*ssh_host_.*_key/\1/g' -scwq /etc/ssh/sshd_config
RUN sudo RUNLEVEL=1 dpkg-reconfigure openssh-server
RUN sudo ssh-keygen -A -v
RUN sudo update-rc.d ssh defaults

RUN sudo pecl install channel://pecl.php.net/xmlrpc-1.0.0RC3  xmlrpc

EXPOSE 22

CMD ["bash", "docker_run.sh"]