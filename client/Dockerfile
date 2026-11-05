# Nuxt client container. Dev mode (NUXT_DEV_MODE=true, the docker-compose
# default) runs the HMR dev server against the bind-mounted source; otherwise
# it serves a production build — which is what CI's Playwright job tests.
FROM node:22-bookworm-slim

WORKDIR /app

ENV NUXT_HOST=0.0.0.0 \
    NUXT_PORT=3000 \
    PORT=3000

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /app/node_modules \
    && chown -R node:node /app

# The image itself runs unprivileged. docker-compose.yml overrides the user
# (CLIENT_UID/CLIENT_GID, default 0:0) because the source is a host bind mount
# whose owner varies between developer machines and CI.
USER node

EXPOSE 3000

ENTRYPOINT ["docker-entrypoint.sh"]
