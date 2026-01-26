FROM php:8.3-apache

WORKDIR /var/www/html

ARG WWWGROUP

RUN apt-get update

RUN apt-get install -y npm
RUN npm install -g n
RUN n lts

RUN apt-get install -y \
    git \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libpq-dev \
    libmagickwand-dev

RUN docker-php-ext-install pdo_mysql zip exif pcntl bcmath gd
RUN a2enmod rewrite

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html

#RUN su www-data

#RUN composer install --no-dev --prefer-dist --no-scripts --no-progress --no-suggest

RUN cd /var/www/html
#RUN composer install
#RUN npm install

RUN chown -R www-data:www-data /var/www/html

RUN npm run watch &

EXPOSE 80
CMD ["apache2-foreground"]
