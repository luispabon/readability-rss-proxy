###########
# Backend #
###########

# Dev env base container
FROM phpdockerio/php74-fpm:latest AS backend-dev
WORKDIR "/application"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        php7.4-pgsql \
        php-redis \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /var/log/* /var/cache/* /usr/share/doc/*

# Pre-deployment container. The deployed container needs some files generated by yarn
FROM backend-dev AS backend-deployment

ENV APP_ENV=prod
ENV APP_SECRET=""

ENV DB_HOST="postgres"
ENV DB_PORT=5432
ENV DB_NAME="foo"
ENV DB_USER="user"
ENV DB_PASSWORD="passwd"
ENV DB_VERSION=9.6
ENV DB_DRIVER=pdo_pgsql
ENV DB_PROTOCOL=pgsql
ENV DB_CHARSET=UTF8

ENV REDIS_HOST="redis"
ENV REDIS_CACHE_PREFIX_SEED="rss-proxy"

ENV READABILITY_JS_ENDPOINT="http://foo/bar/"

COPY bin/console ./bin/
COPY composer.*  ./

RUN composer install --no-dev --no-scripts; \
    composer clear-cache

COPY infrastructure/php-fpm/php-ini-overrides.ini  /etc/php/7.3/fpm/conf.d/z-overrides.ini
COPY infrastructure/php-fpm/opcache-prod.ini       /etc/php/7.3/fpm/conf.d/z-opcache.ini
COPY infrastructure/php-fpm/php-fpm-pool-prod.conf /etc/php/7.3/fpm/pool.d/z-optimised.conf

COPY config           ./config
COPY public/index.php ./public/
COPY src              ./src
COPY templates        ./templates
COPY .env             ./

RUN composer dump-env prod; \
    composer dump-autoload --optimize --classmap-authoritative; \
    bin/console cache:warmup; \
    chown www-data:www-data /tmp/site-cache /tmp/site-logs -Rf

############
# Frontend #
############

## Actual deployable frontend image
FROM nginx:alpine AS frontend-deployment

WORKDIR /application

RUN mkdir ./public; \
    touch ./public/index.php

COPY infrastructure/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# NGINX config: update php-fpm hostname to localhost (same pod in k8s), activate pagespeed config, deactivate SSL
RUN sed -i "s/php-fpm/localhost/g"       /etc/nginx/conf.d/default.conf; \
    sed -i "s/# %DEPLOYMENT //g"         /etc/nginx/conf.d/default.conf; \
    sed -i "s/listen 443/#listen 443/g"  /etc/nginx/conf.d/default.conf; \
    sed -i "s/ssl_/#ssl_/g"              /etc/nginx/conf.d/default.conf

COPY public/favicon.ico ./public/
COPY public/js          ./public/js
COPY public/images      ./public/images
