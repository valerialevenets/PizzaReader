#!/usr/bin/env bash

set -e

INSTALL=0
POSITIONAL=()
while [[ $# -gt 0 ]]
do
key="$1"

case $key in
    --install)
    INSTALL=1
    shift # past argument
    ;;
    *)    # unknown option
    POSITIONAL+=("$1") # save it in an array for later
    shift # past argument
    ;;
esac
done
set -- "${POSITIONAL[@]}" # restore positional parameters

# Starts the app
#cp local.php ../config/autoload/local.php
docker compose up -d --build

# It can return non-zero code - just ignore
set +e
#docker exec prod_backend_1 chmod -R -f 777 data
set -e

docker exec prod_backend_1 composer install
docker exec prod_worker_1 composer install
#docker exec dev_web_1 composer development-enable
#docker exec dev_web_1 php ./bin/migration-wrapper.php

if [ $INSTALL -eq 1 ]
then
   #docker exec dev-backend-1 php ./vendor/bin/doctrine-module orm:fixtures:load -n
fi

echo "Application started"
