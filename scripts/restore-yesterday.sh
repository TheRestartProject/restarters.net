#!/bin/bash
set -euo pipefail

APP_NAME="restarters-db-yesterday"
SOURCE_APP="restarters-db"
VOLUME_NAME="mysqldata"
REGION="lhr"

echo "==> Finding yesterday's snapshot for ${SOURCE_APP}..."

# Get the volume ID
VOL_ID=$(fly volumes list -a "$SOURCE_APP" --json | jq -r '.[0].id')
if [ -z "$VOL_ID" ] || [ "$VOL_ID" = "null" ]; then
    echo "ERROR: No volumes found for ${SOURCE_APP}"
    exit 1
fi
echo "    Volume: ${VOL_ID}"

# Get yesterday's snapshot (second most recent, since most recent is today's)
SNAPSHOT_ID=$(fly volumes snapshots list "$VOL_ID" -a "$SOURCE_APP" --json | jq -r '.[1].id // empty')
if [ -z "$SNAPSHOT_ID" ]; then
    echo "ERROR: No yesterday snapshot found"
    exit 1
fi
echo "    Snapshot: ${SNAPSHOT_ID}"

# Destroy existing app if it exists
echo "==> Cleaning up existing ${APP_NAME} app..."
if fly apps list --json | jq -e ".[] | select(.name == \"${APP_NAME}\")" > /dev/null 2>&1; then
    # Destroy all machines first
    for machine_id in $(fly machines list -a "$APP_NAME" --json | jq -r '.[].id'); do
        echo "    Destroying machine ${machine_id}..."
        fly machine destroy "$machine_id" -a "$APP_NAME" --force 2>/dev/null || true
    done
    # Destroy all volumes
    for vol_id in $(fly volumes list -a "$APP_NAME" --json | jq -r '.[].id'); do
        echo "    Destroying volume ${vol_id}..."
        fly volumes destroy "$vol_id" -a "$APP_NAME" --yes 2>/dev/null || true
    done
    fly apps destroy "$APP_NAME" --yes
    echo "    Destroyed."
else
    echo "    No existing app found."
fi

echo "==> Creating app ${APP_NAME}..."
fly apps create "$APP_NAME"

echo "==> Restoring volume from snapshot ${SNAPSHOT_ID}..."
NEW_VOL_ID=$(fly volumes create "$VOLUME_NAME" \
    -a "$APP_NAME" \
    --region "$REGION" \
    --size 10 \
    --snapshot-id "$SNAPSHOT_ID" \
    --yes \
    --json | jq -r '.id')
echo "    New volume: ${NEW_VOL_ID}"

echo "==> Copying secrets from ${SOURCE_APP}..."
# We need the same MySQL passwords for the restored DB
MYSQL_PASSWORD=$(fly ssh console -a "$SOURCE_APP" -C "printenv MYSQL_PASSWORD" 2>/dev/null || true)
MYSQL_ROOT_PASSWORD=$(fly ssh console -a "$SOURCE_APP" -C "printenv MYSQL_ROOT_PASSWORD" 2>/dev/null || true)

if [ -n "$MYSQL_PASSWORD" ] && [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    fly secrets set -a "$APP_NAME" \
        MYSQL_PASSWORD="$MYSQL_PASSWORD" \
        MYSQL_ROOT_PASSWORD="$MYSQL_ROOT_PASSWORD"
else
    echo "WARNING: Could not read secrets from ${SOURCE_APP}. Set them manually:"
    echo "    fly secrets set MYSQL_PASSWORD=... MYSQL_ROOT_PASSWORD=... -a ${APP_NAME}"
fi

echo "==> Deploying ${APP_NAME}..."
fly deploy --config fly-mysql.toml --app "$APP_NAME" --ha=false

echo "==> Verifying..."
fly status -a "$APP_NAME"

echo ""
echo "Done! Yesterday's DB is running at ${APP_NAME}.internal"
echo ""
echo "To connect phpMyAdmin to it, update PMA_HOST:"
echo "    fly secrets set PMA_HOST=${APP_NAME}.internal -a restarters-pma"
echo ""
echo "Or proxy MySQL directly:"
echo "    fly proxy 3307:3306 -a ${APP_NAME}"
echo ""
echo "To clean up when done:"
echo "    fly apps destroy ${APP_NAME} --yes"
