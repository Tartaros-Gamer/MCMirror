FROM php:7.4-cli-alpine as php-builder
WORKDIR /workspace
RUN apk add unzip \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
COPY . /workspace
RUN composer install

FROM node:lts-alpine as yarn
WORKDIR /workspace
COPY --from=php-builder /workspace /workspace
RUN apk add yarn && yarn install && yarn encore production && rm -rf node_modules

FROM php:7.4-cli-alpine
WORKDIR /workspace
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && docker-php-ext-install -j$(nproc) pcntl
COPY --from=yarn /workspace /workspace

EXPOSE 80
ENTRYPOINT ["/bin/sh", "/workspace/docker/run.sh"]