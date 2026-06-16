# Digest verbindlich, Tag nur lesbar. Dependabot (docker) hält beides per PR aktuell.
FROM php:8.2-apache@sha256:034cb2b91d74209744da4eec2696075ed97f52788668ffa68967fdd6ee6a306f

# libsqlite3-dev liefert die Header, die docker-php-ext-install zum Kompilieren von pdo_sqlite braucht und die das Image nicht mitbringt.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

# Die Variable allein wirkt nicht, erst das Umschreiben der vhost-Konfiguration verhindert den 403 auf der Wurzel-URL.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

RUN mkdir -p /var/www/html/database

# chown muss nach init.php laufen, sonst gehört die als root angelegte DB nicht www-data und Schreibvorgänge scheitern mit "readonly database".
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'php database/init.php' \
    'chown -R www-data:www-data /var/www/html/database' \
    'exec apache2-foreground' \
    > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Image bringt kein curl mit, daher prüft der Check über den PHP-Stream-Wrapper.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$r=@file_get_contents("http://127.0.0.1/health.php"); exit(strpos((string)$r,"\"status\":\"ok\"")!==false?0:1);'

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
