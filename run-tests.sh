#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction
fi

composer dump-autoload
exec ./vendor/bin/phpunit "$@"
