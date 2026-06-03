FROM dunglas/frankenphp

RUN install-php-extensions pdo pdo_mysql mysqli

COPY . /app/public/

EXPOSE 80

CMD ["frankenphp", "run"]