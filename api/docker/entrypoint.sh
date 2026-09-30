#!/bin/sh
# Runs on every start: migrate, cache config, then serve.
set -e
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force
php artisan optimize
exec "$@"
