# syntax=docker/dockerfile:1
# Laravolt v7 starter-kit — dev container.
# Based on laravoltdev/image (ServerSideUp PHP + Laravolt extensions).
# Source tree is mounted via compose; this image only adds bun for frontend.

FROM laravoltdev/image:php8.5-frankenphp-debian

USER root

RUN curl -fsSL https://bun.sh/install | bash \
    && ln -s /root/.bun/bin/bun /usr/local/bin/bun \
    && ln -s /root/.bun/bin/bunx /usr/local/bin/bunx

USER www-data

ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVOLT_LINK=true
