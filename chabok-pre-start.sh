#!/usr/bin/env bash

# Cloudiwa runs this script before the web process on every container start.
# Keep the image-lab queue in a supervised loop so a completed, failed, or
# restarted worker is replaced without taking the public website down.
set -u

ROOT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
cd "$ROOT_DIR"

if [ "${QUEUE_CONNECTION:-database}" != "database" ]; then
    exit 0
fi

mkdir -p storage/logs storage/app

# Apply production database changes before starting the queue worker/web process.
# Two startup hooks can overlap briefly on a rolling deploy. A duplicate-column
# error in that narrow race is retried after the first migrator has committed;
# every other migration error remains fatal and is visible in the log.
MIGRATION_LOG="storage/logs/migration.log"
MIGRATION_ATTEMPT="storage/app/migration-attempt.$$.log"
trap 'rm -f "$MIGRATION_ATTEMPT"' EXIT

php artisan migrate --force --no-interaction > "$MIGRATION_ATTEMPT" 2>&1
MIGRATION_STATUS=$?
cat "$MIGRATION_ATTEMPT" >> "$MIGRATION_LOG"

if [ "$MIGRATION_STATUS" -ne 0 ]; then
    if grep -Eqi 'duplicate column|already exists' "$MIGRATION_ATTEMPT"; then
        sleep 2
        php artisan migrate --force --no-interaction > "$MIGRATION_ATTEMPT" 2>&1
        MIGRATION_STATUS=$?
        cat "$MIGRATION_ATTEMPT" >> "$MIGRATION_LOG"
    fi

    if [ "$MIGRATION_STATUS" -ne 0 ]; then
        echo "Database migration failed; refusing to start the service." >&2
        exit 1
    fi
fi

# The pre-start hook can be called more than once during a deploy. Keep an
# advisory lock open in the child worker so only one loop consumes the queue.
WORKER_LOCK="storage/app/queue-worker.lock"
exec 9>"$WORKER_LOCK"
if command -v flock >/dev/null 2>&1; then
    if ! flock -n 9; then
        exit 0
    fi
elif ps -eo args 2>/dev/null | grep -F 'artisan queue:work database' | grep -v grep >/dev/null 2>&1; then
    exit 0
fi

(
    while true; do
        php artisan queue:work database \
            --queue=default \
            --sleep=3 \
            --tries=1 \
            --timeout="${QUEUE_WORKER_TIMEOUT:-900}" \
            --memory="${QUEUE_WORKER_MEMORY:-256}" \
            --max-time="${QUEUE_WORKER_MAX_TIME:-3600}"

        # `queue:restart` intentionally makes the worker exit. Restart it
        # after a short pause; unexpected worker exits are handled the same way.
        sleep 2
    done
) >> storage/logs/queue-worker.log 2>&1 &

echo $! > storage/app/queue-worker.pid
