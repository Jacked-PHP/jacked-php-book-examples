FROM openswoole/swoole:php8.3

ARG ENVIRONMENT_NAME

COPY ./rootfilesystem/ /

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

WORKDIR "/var/www/html"