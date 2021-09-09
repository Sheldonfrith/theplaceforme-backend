FROM bitnami/laravel:latest
WORKDIR /docker
COPY Laravel/package.json /docker/Laravel/package.json
RUN cd Laravel && sudo npm install
COPY Laravel/composer.json /docker/Laravel/composer.json
RUN cd Laravel && sudo php composer install
COPY . /docker
