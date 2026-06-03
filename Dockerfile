FROM php:8.2-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN apk add --no-cache nginx bash

COPY . /var/www/html/

RUN mkdir -p /run/nginx

RUN printf 'server {\n\
    listen 80;\n\
    root /var/www/html;\n\
    index index.php index.html;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
    }\n\
}\n' > /etc/nginx/http.d/default.conf

RUN printf '#!/bin/sh\n\
php-fpm -D\n\
sleep 1\n\
nginx -g "daemon off;"\n' > /start.sh

RUN chmod +x /start.sh

EXPOSE 80

CMD ["/bin/sh", "/start.sh"]