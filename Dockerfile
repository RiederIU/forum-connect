# Basis-Image per Digest festgelegt für reproduzierbare Builds. Der Tag 8.2-apache
# bleibt als lesbarer Hinweis, verbindlich ist der Digest. Dependabot (Ökosystem
# docker) hält Tag und Digest über Pull Requests aktuell, sodass Reproduzierbarkeit
# und Sicherheits-Patches sich nicht ausschließen.
FROM php:8.2-apache@sha256:affc043fbd9acaa9a6394a71d162726fc0a6e4bea0400a3b94f925b6130858dd

# SQLite-PDO-Treiber, den die Anwendung über getDB() voraussetzt. Das
# php:8.2-apache-Image bringt nur die SQLite-Laufzeitbibliothek mit, nicht die
# Entwicklungsheader (libsqlite3-dev), die docker-php-ext-install zum
# Kompilieren von pdo_sqlite benötigt. Daher werden sie vorher installiert und
# der apt-Cache anschließend wieder entfernt, damit das Image schlank bleibt.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

# Im offiziellen Image zeigt der DocumentRoot auf /var/www/html, die Anwendung liegt
# aber unter public/. Die Variable allein genügt nicht, daher werden die
# vhost-Konfigurationen umgeschrieben, sonst landet ein Aufruf der Wurzel-URL im leeren Verzeichnis (403).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

# Ablageort der SQLite-Datei, den init.php zur Laufzeit befüllt.
RUN mkdir -p /var/www/html/database

# Entrypoint zur Laufzeit. Reihenfolge ist kritisch: init.php legt die DB als
# root an, daher muss der chown danach laufen, sonst bleibt die Datei
# schreibgeschützt und Schreibvorgänge scheitern mit "readonly database".
# set -e bricht den Start ab, falls init.php fehlschlägt, statt mit leerer DB
# weiterzulaufen.
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'php database/init.php' \
    'chown -R www-data:www-data /var/www/html/database' \
    'exec apache2-foreground' \
    > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Laufender Gesundheitscheck. Das php:8.2-apache-Image bringt kein curl mit, daher
# ruft der Check den Health-Endpoint mit dem in PHP eingebauten Stream-Wrapper auf
# und prüft auf status ok. start-period gibt dem Entrypoint Zeit, init.php
# auszuführen und die Schreibrechte per chown zu setzen, bevor der erste Check zählt.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD php -r '$r=@file_get_contents("http://127.0.0.1/health.php"); exit(strpos((string)$r,"\"status\":\"ok\"")!==false?0:1);'

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
