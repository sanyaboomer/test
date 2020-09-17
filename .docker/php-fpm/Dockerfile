FROM php:7.3-fpm

# Frontend build stuff
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash - \
&& curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - \
&& echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
	wget \
    libzip-dev \
    zip \
	libxml2-dev \
	libldap2-dev \
	gnupg \
	apt-transport-https \
	nodejs \
	yarn \
    libicu-dev \
    g++

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
&& composer --version

# Type docker-php-ext-install to see available extensions
RUN pecl install redis-4.1.0 apcu

RUN docker-php-ext-configure ldap \
&& docker-php-ext-configure zip --with-libzip \
&& docker-php-ext-install pdo pdo_mysql bcmath zip opcache soap mbstring ldap pcntl \
&& docker-php-ext-enable redis apcu

RUN docker-php-ext-configure intl \
&& docker-php-ext-install intl

RUN echo 'alias sf="php app/console"' >> ~/.bashrc \
&& echo 'alias sf3="php bin/console"' >> ~/.bashrc

EXPOSE 9000
WORKDIR /var/www