# Set the base image for subsequent instructions
FROM php:7.4

# Update packages
RUN apt-get update

# Install PHP and composer dependencies
RUN apt-get update \
    && apt-get install -y libpq-dev libonig-dev libgmp-dev libxml2-dev libpng-dev libicu-dev zlib1g-dev libzip-dev libbz2-dev git zip \
    && rm -rf /var/lib/apt/lists/*

# Clear out the local repository of retrieved package files
RUN apt-get clean

# Install needed extensions
# Here you can install any other extension that you need during the test and deployment process
RUN docker-php-ext-install \
        mbstring \
        opcache \
        pdo pdo_mysql \
        sockets \
        bcmath \
        intl zip gd bcmath bz2 mbstring xml

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
