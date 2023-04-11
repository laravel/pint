FROM composer:2.2

ARG VERSION=@dev

RUN composer global require laravel/pint:"${VERSION}" \
        --no-interaction \
        --no-progress \
        --no-suggest \
        --no-scripts

ENV PATH="/tmp/vendor/bin:${PATH}"

ENTRYPOINT ["pint"]
