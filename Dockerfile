FROM dunglas/frankenphp

RUN install-php-extensions pdo pdo_mysql mysqli

COPY . /app/public/

RUN printf '{\n\
    admin off\n\
}\n\
:{$PORT:8080} {\n\
    root * /app/public\n\
    php_server\n\
}\n' > /etc/caddy/Caddyfile

EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]