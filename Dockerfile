# Gunakan base image PHP versi terbaru
FROM php:8.3-apache

# Install ekstensi dan utilitas yang dibutuhkan Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Ambil binary Composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory ke root web server
WORKDIR /var/www/html

# Salin semua source code ke dalam container
COPY . .

# Konfigurasi Apache agar mengarah ke folder /public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Install dependencies PHP dan build frontend Vite
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Beri permission ke folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Buka port standar Apache
EXPOSE 80

CMD ["apache2-foreground"]