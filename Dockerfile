FROM wordpress:6.6-php8.3-fpm

# Install PHP extensions: gd (with jpeg/freetype/webp), mysqli, imagick.
RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libwebp-dev \
    libmagickwand-dev \
    git \
  ; \
  docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp; \
  docker-php-ext-install -j"$(nproc)" gd mysqli; \
  if ! php -m | grep -qi '^imagick$'; then \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
  else \
    echo "imagick already present, skipping pecl install"; \
  fi; \
  rm -rf /var/lib/apt/lists/* /tmp/pear

# Install WP-CLI for plugin/theme management in build/runtime.
RUN set -eux; \
  curl -o /usr/local/bin/wp -fSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; \
  chmod +x /usr/local/bin/wp

# Switch back to the default working directory used by the image.
WORKDIR /var/www/html
