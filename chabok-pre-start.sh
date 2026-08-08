#!/usr/bin/env bash

# Cloudiwa runs this script before the web process on every container start.
# Keep the image-lab queue in a supervised loop so a completed, failed, or
# restarted worker is replaced without taking the public website down.
set -u

if [ "${QUEUE_CONNECTION:-database}" != "database" ]; then
    exit 0
fi

mkdir -p storage/logs storage/app

(
    while true; do
        php artisan queue:work database \
            --queue=default \
            --sleep=3 \
            --tries=1 \
            --timeout="${QUEUE_WORKER_TIMEOUT:-600}" \
            --memory="${QUEUE_WORKER_MEMORY:-256}" \
            --max-time="${QUEUE_WORKER_MAX_TIME:-3600}"

        # `queue:restart` intentionally makes the worker exit. Restart it
        # after a short pause; unexpected worker exits are handled the same way.
        sleep 2
    done
) >> storage/logs/queue-worker.log 2>&1 &

echo $! > storage/app/queue-worker.pid
