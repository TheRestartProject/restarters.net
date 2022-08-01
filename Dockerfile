# This is the docker for restarters.  It's used from docker-compose.
FROM cimg/php:7.4.11-node

# Set working directory to where we will run.
WORKDIR /var/www

# Install dependencies
RUN sudo apt-get update && \
    sudo apt install dnsutils openssl zip unzip git libxml2-dev libzip-dev zlib1g-dev libcurl4-openssl-dev iputils-ping default-mysql-client vim libpng-dev libgmp-dev libjpeg-turbo8-dev && \
    sudo apt install php7.4-xmlrpc php7.4-intl php7.4-xdebug php7.4-xmlrpc php7.4-mbstring php7.4-simplexml php7.4-curl php7.4-zip python postgresql-client

# Clear cache - reduces image size.
RUN sudo apt-get clean && sudo rm -rf /var/lib/apt/lists/*

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

# Install sshd
RUN sudo apt-get update
RUN sudo apt-get install vim openssh-server
RUN sudo sed 's@session\s*required\s*pam_loginuid.so@session optional pam_loginuid.so@g' -i /etc/pam.d/sshd
RUN sudo mkdir /var/run/sshd
RUN sudo bash -c 'install -m755 <(printf "#!/bin/sh\nexit 0") /usr/sbin/policy-rc.d'
RUN sudo ex +'%s/^#\zeListenAddress/\1/g' -scwq /etc/ssh/sshd_config
RUN sudo ex +'%s/^#\zeHostKey .*ssh_host_.*_key/\1/g' -scwq /etc/ssh/sshd_config
RUN sudo RUNLEVEL=1 dpkg-reconfigure openssh-server
RUN sudo ssh-keygen -A -v
RUN sudo update-rc.d ssh defaults

RUN sudo apt-get install python2

EXPOSE 22
CMD ["/usr/sbin/sshd", "-D"]

CMD ["bash", "docker_run.sh"]