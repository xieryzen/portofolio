FROM php:8.3-cli

# Ekstensi yang dibutuhkan (pdo_sqlite & mbstring sudah bawaan image resmi).
RUN docker-php-ext-install pdo_mysql >/dev/null 2>&1 || true

WORKDIR /app

COPY . /app

RUN mkdir -p storage/uploads storage/sessions storage/logs \
    && chmod -R 775 storage \
    && chmod +x start.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_PORT=8080 \
    TZ=Asia/Jakarta

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r "exit(@fsockopen('127.0.0.1', (int)(getenv('APP_PORT') ?: 8080)) ? 0 : 1);"

CMD ["bash", "start.sh"]
