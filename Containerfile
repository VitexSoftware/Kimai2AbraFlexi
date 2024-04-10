FROM php:8.2-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && install-php-extensions gettext intl zip soap
COPY src /usr/src/kimai2abraflexi/src
RUN sed -i -e 's/..\/.env//' /usr/src/kimai2abraflexi/src/*.php
COPY composer.json /usr/src/kimai2abraflexi
WORKDIR /usr/src/kimai2abraflexi
RUN curl -s https://getcomposer.org/installer | php
RUN ./composer.phar install
WORKDIR /usr/src/kimai2abraflexi/src
CMD [ "php", "./kimai2abraflexi.php" ]
