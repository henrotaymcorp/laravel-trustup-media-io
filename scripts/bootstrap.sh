#!/bin/bash
docker compose build && \
./cli composer install && \
npx lefthook install
