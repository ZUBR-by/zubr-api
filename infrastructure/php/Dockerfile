FROM php:8.0-fpm

RUN apt-get update \
    && apt-get install -y libpq-dev libonig-dev libgmp-dev libxml2-dev libwebp-dev libjpeg62-turbo-dev libpng-dev libxpm-dev libfreetype6-dev libicu-dev zlib1g-dev libzip-dev libbz2-dev git zip \
    && rm -rf /var/lib/apt/lists/*
RUN apt-get update
RUN apt-get install -y libcairo2-dev libjpeg62-turbo-dev libpango1.0-dev libgif-dev build-essential g++ freetype2-demos

RUN docker-php-ext-configure gd --with-jpeg=/usr/include/ --with-freetype=/usr/include/

RUN docker-php-ext-install \
        mbstring \
        opcache \
        pdo pdo_mysql \
        sockets \
        bcmath \
        gd \
        intl zip gd bcmath bz2 mbstring xml

RUN pecl install xdebug

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN curl --silent --show-error https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer && \
    composer clear-cache

COPY infrastructure/php/config/www.conf /usr/local/etc/php-fpm.d/www.conf
CMD ["php-fpm", "--allow-to-run-as-root"]

ADD composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-suggest && \
    composer clear-cache
COPY infrastructure/php/php.ini $PHP_INI_DIR/conf.d/
COPY . /app/
RUN composer dump-autoload --optimize
