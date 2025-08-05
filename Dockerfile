FROM public.ecr.aws/docker/library/php:8.3-fpm as base

RUN apt-get update && apt-get install -y \
    # Needed for php zip extension
    libzip-dev \
    zip \
    # Needed for php intl extension
    libicu-dev \
    libpq-dev \
    # Needed for php imagick extension
    libmagickwand-dev

# Add xdebug if on a local environment
ARG INSTALL_XDEBUG=0
ARG ENABLE_GRPC=0

RUN if [ $INSTALL_XDEBUG = 1 ]; then \
    pecl install xdebug ; \
    echo 'zend_extension=xdebug.so' > "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
    echo 'xdebug.mode=develop,debug' >> "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
    echo 'xdebug.start_with_request=yes' >> "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
    echo 'xdebug.discover_client_host=1' >> "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
    echo 'xdebug.client_host=host.docker.internal' >> "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
    echo 'xdebug.output_dir=/tmp/docker-xdebug' >> "${PHP_INI_DIR}/conf.d/90-xdebug.ini" ; \
fi

# data dog
RUN curl -LO https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php && php datadog-setup.php --php-bin=all

# Copy php.ini config file into image
COPY .docker/php/php.ini $PHP_INI_DIR/php.ini

# `pcov` extension is needed for phpunit code coverage reports
RUN pecl install pcov && docker-php-ext-enable pcov

# Install the redis extension using pecl
RUN pecl install redis && docker-php-ext-enable redis

# Install the imagick extension using pecl
RUN pecl install imagick && docker-php-ext-enable imagick

# Install php extensions
RUN docker-php-ext-install pcntl intl zip pdo pdo_pgsql bcmath

# While Google Optimization AI is not used often in ARO, grpc extension can be disabled, as it takes a lot of time to build the container.
# In case engine will be used frequently it is recommended to enable grpc.
RUN if [ $ENABLE_GRPC = 1 ]; then \
    pecl install grpc ;\
    echo 'extension=grpc.so' > "${PHP_INI_DIR}/conf.d/20-grpc.ini" ; \
fi

RUN mkdir -p /var/www/app

WORKDIR /var/www/app

COPY ./ .

# Install composer
COPY --from=public.ecr.aws/composer/composer:2.5.5 /usr/bin/composer /usr/local/bin/composer

# Configure auth for composer to custom package repository
ARG COMPOSER_AUTH_TOKEN
RUN composer config --global --auth http-basic.aptive.repo.repman.io token ${COMPOSER_AUTH_TOKEN}

RUN composer install \
    -v \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

RUN chown -R www-data:www-data /var/www/app

USER www-data

CMD ["php-fpm"]
