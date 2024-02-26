!/bin/bash

# check if composer.json and phpunit.xml exist
if [ ! -f "composer.json" ]; then
    echo "composer.json not found"
    exit 1
fi

# check if phpunit.xml exists
if [ ! -f "phpunit.xml" ]; then
    echo "phpunit.xml not found"
    exit 1
fi

# if there is no vendor directory, run composer install
if [ ! -d "vendor" ]; then
    composer install
fi
composer dump-autoload

./vendor/bin/phpunit