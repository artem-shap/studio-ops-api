# The host has no native PHP runtime, so the application brings its own.
# The minor version is pinned: a surprise major in a base image is the kind of
# breakage that only shows up at deploy time.
FROM dunglas/frankenphp:1.12-php8.5-alpine AS builder

RUN install-php-extensions pdo_pgsql opcache intl

# Node lives in the build stage only. It is needed here rather than in a
# separate node stage because Wayfinder generates its TypeScript route helpers
# by invoking artisan during the Vite build, so the asset build needs PHP.
RUN apk add --no-cache nodejs npm \
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

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

COPY . .

RUN composer dump-autoload --optimize --no-dev \
 && pnpm run build \
 && rm -rf node_modules


# Runtime carries no build tooling: no Node, no pnpm, no dev dependencies.
FROM dunglas/frankenphp:1.12-php8.5-alpine AS runtime

RUN install-php-extensions pdo_pgsql opcache intl

WORKDIR /app

COPY --from=builder /app /app
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
