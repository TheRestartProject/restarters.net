#!/bin/bash
# Daily restart of restarters-yesterday so it picks up a fresh backup.
# Requires FLY_YESTERDAY_RESTART_TOKEN secret set on the production app.

# On Fly.io, /fly/init is PID 1 with minimal env; secrets live in supervisord.
ENVIRON_SOURCE=/proc/1/environ
SUPER_PID=$(pgrep -f supervisord 2>/dev/null | head -1)
[ -n "$SUPER_PID" ] && ENVIRON_SOURCE=/proc/$SUPER_PID/environ
while IFS= read -r -d '' var; do
    case "$var" in FLY_*) export "$var" ;; esac
done < "$ENVIRON_SOURCE"

[ -z "$FLY_YESTERDAY_RESTART_TOKEN" ] && exit 0

APP="restarters-yesterday"
LOG=/var/log/db-backup.log

MACHINES=$(curl -sf \
    -H "Authorization: Bearer $FLY_YESTERDAY_RESTART_TOKEN" \
    "https://api.machines.dev/v1/apps/$APP/machines" 2>/dev/null) || {
    echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - could not list $APP machines" >> "$LOG"
    exit 1
}

echo "$MACHINES" | grep -o '"id":"[^"]*"' | cut -d'"' -f4 | while IFS= read -r id; do
    [ -z "$id" ] && continue
    if curl -sf -X POST \
        -H "Authorization: Bearer $FLY_YESTERDAY_RESTART_TOKEN" \
        "https://api.machines.dev/v1/apps/$APP/machines/$id/restart" > /dev/null 2>&1; then
        echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Restarted $APP machine $id" >> "$LOG"
    else
        echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - failed to restart $APP machine $id" >> "$LOG"
    fi
done
