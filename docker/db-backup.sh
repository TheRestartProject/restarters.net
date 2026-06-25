#!/bin/bash
# Hourly MySQL backup to Google Drive
# Cron runs this hourly (see Dockerfile.fly); logs to /var/log/db-backup.log

LOG=/var/log/db-backup.log

# Cron does not inherit container env vars. On Fly.io, /fly/init is PID 1 (minimal
# env); secrets live in supervisord's environ. Find it and read from there.
ENVIRON_SOURCE=/proc/1/environ
SUPER_PID=$(pgrep -f supervisord 2>/dev/null | head -1)
[ -n "$SUPER_PID" ] && ENVIRON_SOURCE=/proc/$SUPER_PID/environ
while IFS= read -r -d '' var; do
    case "$var" in DB_*|GDRIVE_*|RCLONE_*) export "$var" ;; esac
done < "$ENVIRON_SOURCE"

# Exit silently if GDRIVE_BACKUP_FOLDER_ID is not set (dev environments)
if [ -z "$GDRIVE_BACKUP_FOLDER_ID" ]; then
    exit 0
fi

# Log timestamp
echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Starting database backup..." >> "$LOG"

# Verify required env vars
MISSING=""
for var in DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD GDRIVE_BACKUP_FOLDER_ID; do
    if [ -z "$(eval echo \$$var)" ]; then
        MISSING="$MISSING $var"
    fi
done

if [ -n "$MISSING" ]; then
    echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - missing environment variables:$MISSING" >> "$LOG"
    exit 1
fi

# Create temporary backup file
BACKUP_FILE="/tmp/db-backup-$(date +%Y%m%d-%H%M%S).sql.gz"
BACKUP_NAME="$(basename "$BACKUP_FILE")"

# Dump database to compressed file
if ! mysqldump \
    -h "$DB_HOST" \
    -u "$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    --skip-ssl \
    --single-transaction \
    --quick \
    --lock-tables=false \
    "$DB_DATABASE" 2>>"$LOG" | gzip > "$BACKUP_FILE"; then
    echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - mysqldump failed" >> "$LOG"
    rm -f "$BACKUP_FILE"
    exit 1
fi

# Upload to Google Drive using rclone
# --drive-root-folder-id targets the folder by its Drive ID (not by name path)
# Rate-limit / retry caps (--tpslimit, --drive-pacer-min-sleep, --retries,
# --low-level-retries) ensure a transient Drive error can never become a request
# storm. --drive-chunk-size 64M cuts the number of upload requests ~8x vs the 8M default.
if ! rclone copy "$BACKUP_FILE" "gdrive:" \
    --drive-root-folder-id="$GDRIVE_BACKUP_FOLDER_ID" \
    --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" \
    --tpslimit=2 --drive-pacer-min-sleep=200ms \
    --retries=2 --low-level-retries=3 \
    --drive-chunk-size=64M \
    --log-file="$LOG" \
    --log-level=INFO 2>>"$LOG"; then
    echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - rclone upload failed" >> "$LOG"
    rm -f "$BACKUP_FILE"
    exit 1
fi

echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Uploaded $BACKUP_NAME to Google Drive" >> "$LOG"

# Clean up local backup file
rm -f "$BACKUP_FILE"

# Prune old backups once per day, at 03:00 UTC, rather than every hour. Retention is
# 168 hourly backups (7 days), so the list+delete pass only needs to run daily; doing
# it every hour just wastes Drive API calls. Between prunes the count drifts up to ~192
# (8 days), which is harmless.
#
# IMPORTANT: deletion requires the backup service account to have **Content Manager**
# (not just Contributor) on the Shared Drive. With only Contributor it can upload and
# list but every delete is rejected (403 insufficientFilePermissions / 404), so backups
# accumulate without bound and the hourly retry of rejected deletes becomes a Drive API
# request storm. Grant Content Manager on the Shared Drive for this to actually prune.
if [ "$(date -u +%H)" = "03" ]; then
    # Keep only the last 168 hourly backups on Google Drive.
    # List all backups newest-first and delete everything past the 168th.
    BACKUPS=$(rclone lsf "gdrive:" --drive-root-folder-id="$GDRIVE_BACKUP_FOLDER_ID" --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" --tpslimit=2 --drive-pacer-min-sleep=200ms --retries=2 --low-level-retries=3 --format=p 2>/dev/null | grep 'db-backup-.*\.sql\.gz' | sort -r)
    BACKUP_COUNT=$(echo "$BACKUPS" | grep -c 'db-backup')

    if [ "$BACKUP_COUNT" -gt 168 ]; then
        BACKUPS_TO_DELETE=$(echo "$BACKUPS" | tail -n +169)
        while IFS= read -r backup; do
            if [ -n "$backup" ]; then
                echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Deleting old backup: $backup" >> "$LOG"
                # `rclone deletefile` removes a single named file. (`rclone delete` treats its
                # argument as a directory to enumerate, so for a file path it deletes nothing.)
                # Deletes go to the Shared Drive trash (auto-emptied after 30 days). Do NOT add
                # --drive-use-trash=false: permanent delete returns 404 for this service account
                # on the Shared Drive; the trash path is what actually works.
                rclone deletefile "gdrive:$backup" --drive-root-folder-id="$GDRIVE_BACKUP_FOLDER_ID" --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" --tpslimit=2 --drive-pacer-min-sleep=200ms --retries=2 --low-level-retries=3 2>>"$LOG" || true
            fi
        done <<< "$BACKUPS_TO_DELETE"
    fi
fi

echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Database backup completed successfully" >> "$LOG"
