FROM php:8.3-cli
WORKDIR /app
COPY . /app/
RUN cp -r /app/data /defaults-data && \
    printf '#!/bin/bash\nif [ ! -f /app/data/settings.json ]; then cp -rn /defaults-data/. /app/data/; fi\nexec php -S 0.0.0.0:${PORT:-8080} -t /app /app/router.php\n' > /entrypoint.sh && chmod +x /entrypoint.sh
ENV PORT=8080
EXPOSE 8080
CMD ["/entrypoint.sh"]
# Sun Jun 14 00:08:51 +07 2026
