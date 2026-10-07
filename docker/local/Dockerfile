# Local development image: PHP 8.4 + the extensions the ERP needs.
# Used by the "app" profile in docker-compose.yml — not for production.
FROM php:8.4-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql intl zip bcmath gd exif pcntl opcache \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip postgresql-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Project files are bind-mounted from the host; opcache must re-check them.
RUN { \
      echo 'opcache.enable_cli=1'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.revalidate_freq=0'; \
      echo 'memory_limit=512M'; \
      echo 'upload_max_filesize=20M'; \
      echo 'post_max_size=25M'; \
    } > /usr/local/etc/php/conf.d/local.ini

WORKDIR /app
EXPOSE 8000

ENTRYPOINT ["bash", "/app/docker/local/entrypoint.sh"]
