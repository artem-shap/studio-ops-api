# Debian, not Alpine.
#
# The Vue starter kit pins its native binaries to glibc builds —
# @rollup/rollup-linux-x64-gnu, @tailwindcss/oxide-linux-x64-gnu and
# lightningcss-linux-x64-gnu. On Alpine those cannot load at all, so the Vite
# build fails and takes the whole image with it. The OS is pinned explicitly to
# trixie so the Node binaries copied in below sit on the same glibc.
FROM dunglas/frankenphp:1.12-php8.5-trixie AS builder

# Composer comes from its own image rather than being assumed present.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_pgsql opcache intl

# Node lives in the build stage only. It is here rather than in a separate node
# stage because Wayfinder generates its TypeScript route helpers by invoking
# artisan during the Vite build, so the asset build needs PHP present.
COPY --from=node:22-trixie-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-trixie-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -sf /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
 && ln -sf /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
 && npm install --global pnpm@11

WORKDIR /app

# Dependencies first, so a change to application code does not invalidate them.
COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --no-interaction

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

COPY . .

RUN composer dump-autoload --optimize --no-dev \
 && pnpm run build \
 && rm -rf node_modules


# Runtime carries no build tooling: no Node, no pnpm, no Composer, no dev
# dependencies.
FROM dunglas/frankenphp:1.12-php8.5-trixie AS runtime

RUN install-php-extensions pdo_pgsql opcache intl

WORKDIR /app

COPY --from=builder /app /app

# .dockerignore strips the contents of these directories, and Docker does not
# create empty ones, so they arrive missing. Laravel does not create them
# either: view:cache fails at startup with "Please provide a valid cache path".
RUN mkdir -p       storage/framework/cache/data       storage/framework/sessions       storage/framework/views       storage/logs       bootstrap/cache  && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
