#!/bin/sh
set -e

# All three caches are written here rather than during the image build.
#
# config:cache resolves env() at the moment it runs, and on this host the real
# environment only exists at runtime, so caching it at build time would bake in
# empty values. route:cache and view:cache boot the framework, which wants
# APP_KEY present; that is also a runtime value.
#
# The cost is roughly a tenth of a second on a cold start that already takes
# most of a minute while the free-tier container wakes.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Only pending migrations run, so this is safe on every wake, not just deploys.
php artisan migrate --force

# FrankenPHP's default Caddyfile listens on {$SERVER_NAME}. A leading colon with
# no hostname means plain HTTP on that port and no certificate negotiation,
# which is what is wanted behind the platform's own TLS terminator.
export SERVER_NAME=":${PORT:-8080}"

# --config is not optional. `frankenphp run` on its own starts Caddy with an
# empty configuration: the admin endpoint comes up on localhost:2019 and
# nothing ever listens on 0.0.0.0, so the platform's port scan finds nothing
# and times out while the container sits there looking healthy.
CADDYFILE=/etc/frankenphp/Caddyfile
[ -f "$CADDYFILE" ] || CADDYFILE=/etc/caddy/Caddyfile

if [ ! -f "$CADDYFILE" ]; then
    echo "No Caddyfile found at either known path; refusing to start blind." >&2
    exit 1
fi

echo "Serving on ${SERVER_NAME} using ${CADDYFILE}"

exec frankenphp run --config "$CADDYFILE"
