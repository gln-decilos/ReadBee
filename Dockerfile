# Frontend build
FROM node:18 AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build


# Backend
FROM php:8.2-cli AS backend

RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev zip nodejs npm

RUN docker-php-ext-install zip pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# Copy Vite build files
COPY --from=frontend /app/dist ./public/build

RUN composer install

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=10000
