ARG PRESTASHOP_VERSION
FROM prestashop/prestashop:${PRESTASHOP_VERSION:-latest}

# ENV XDEBUG_CONFIG="remote_enable=on remote_connect_back=on mode=debug"
# ENV XDEBUG_MODE="debug"

# Last command clear the apt cache and remove all the lists files, to keep the image size smaller.
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && apt-get update \
 && apt-get install -y nodejs npm \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && echo 'max_execution_time = 120' >> /usr/local/etc/php/conf.d/docker-php-maxexectime.ini \
 && apt-get clean && rm -rf /var/lib/apt/lists/*;

# Copy XDdebug configration for remote debugging
COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

#Run prestashop scripts
RUN mkdir -p /tmp/post-install-scripts /tmp/init-scripts /tmp/pre-install-scripts/
COPY ./init-scripts/* /tmp/init-scripts/
COPY ./post-install-scripts/* /tmp/post-install-scripts/
# Add pre install scripts to install vendors for composer and node
COPY ./pre-install-scripts/* /tmp/pre-install-scripts/
RUN chmod 777 /tmp/post-install-scripts/*.sh /tmp/init-scripts/*.sh /tmp/pre-install-scripts/*.sh
