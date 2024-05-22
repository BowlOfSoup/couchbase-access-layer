FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
        wget build-essential checkinstall zlib1g-dev libssl-dev libxml2-dev libzip-dev default-jre git \
        && wget https://github.com/Kitware/CMake/releases/download/v3.27.1/cmake-3.27.1.tar.gz \
        && tar -zxvf cmake-3.27.1.tar.gz \
        && cd cmake-3.27.1 \
        && ./bootstrap \
        && make \
        && make install

RUN wget http://pear.php.net/go-pear.phar && php go-pear.phar

RUN pecl install couchbase

RUN docker-php-ext-install \
        bcmath \
        mysqli \
        opcache \
        pcntl \
        pdo_mysql \
        soap \
        sockets \
        sysvmsg \
        sysvsem \
        sysvshm \
        zip

RUN docker-php-ext-enable couchbase

# Install XDebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

ADD docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
