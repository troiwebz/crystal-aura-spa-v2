FROM php:8.3-apache
RUN a2enmod rewrite && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
# Railway injects PORT; make Apache listen on it
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
# Keep pristine copy of data to seed an empty volume on first boot
RUN cp -r /var/www/html/data /defaults-data
RUN printf '#!/bin/bash\nif [ ! -f /var/www/html/data/settings.json ]; then cp -rn /defaults-data/. /var/www/html/data/; fi\nchown -R www-data:www-data /var/www/html/data\nexec apache2-foreground\n' > /entrypoint.sh && chmod +x /entrypoint.sh
ENV PORT=80
EXPOSE 80
CMD ["/entrypoint.sh"]
