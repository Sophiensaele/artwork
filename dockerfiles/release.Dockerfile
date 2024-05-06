FROM ubuntu:22.04

MAINTAINER "Caldero Systems GmbH"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

ARG BRANCH
ARG TAG

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y ca-certificates  \
    gcc \
    curl \
    git \
    sudo \
    gosu \
    nano \
    cron \
    build-essential \
    ca-certificates \
    gnupg \
    redis \
    libxml2-dev \
    mysql-client \
    mysql-server \
    openssl \
    unzip \
    libxml2-dev \
    libpng-dev \
    libzip-dev \
    libxslt-dev \
    imagemagick\
    libmagickwand-dev \
    wget \
    htop \
    nginx \
    python2 \
    supervisor \
    dnsutils \
    librsvg2-bin \
    python3 \
    python3-pip

RUN mkdir -p /etc/apt/keyrings \
    && echo "deb [trusted=yes] https://apt.fury.io/meilisearch/ /" | tee /etc/apt/sources.list.d/fury.list \
    && echo "deb [trusted=yes] https://ppa.launchpadcontent.net/ondrej/php/ubuntu/ jammy main " | tee /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && echo "deb-src [trusted=yes] https://ppa.launchpadcontent.net/ondrej/php/ubuntu/ jammy main " >> /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
           php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
           php8.2-curl \
           php8.2-imap php8.2-mysql php8.2-mbstring \
           php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
           php8.2-intl php8.2-readline \
           php8.2-ldap \
           php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
           php8.2-memcached php8.2-pcov \
           meilisearch \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
               && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
               && apt-get update \
               && apt-get install -y nodejs \
               && npm install -g npm

RUN docker-php-ext-install pdo_mysql bcmath dom intl zip xsl simplexml sysvsem pcntl gd mysqli sockets exif

COPY dockerfiles/php/fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

RUN git config --global --add safe.directory /var/www/html

RUN git init  \
    && git remote add origin https://github.com/artwork-software/artwork.git  \
    && git pull origin main  \
    && git checkout main

RUN curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN if [ -n "$BRANCH"]; then \
     git checkout $BRANCH; \
    elif [ -n "$TAG" ]; then  \
      git checkout tags/$TAG; \
    fi

RUN pecl install redis imagick && docker-php-ext-enable redis imagick

RUN npm -g install cross-env webpack soketi
RUN npm install && npm run dev && npm run prod

RUN COMPOSER_ALLOW_SUPERUSER=1 composer --no-interaction install

RUN php /var/www/html/artisan storage:link

RUN chown -R www-data:www-data /var/www/html

COPY dockerfiles/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.2/cli/conf.d/99-sail.ini

ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
