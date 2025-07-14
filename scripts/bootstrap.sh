#!/bin/bash
docker run \
  -it \
  --rm \
  --user node:node \
  -v "$PWD":/usr/src/app \
  -w /usr/src/app node:18-alpine \
  yarn install && \
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs && \
docker compose build --no-cache
