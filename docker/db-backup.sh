#!/bin/bash
# Daily MySQL backup to Google Drive
# Cron runs this daily at 2am UTC; logs to /var/log/db-backup.log

LOG=/var/log/db-backup.log

# Cron does not inherit container env vars — read from the init process.
while IFS= read -r -d '' var; do
    case "$var" in DB_*|GDRIVE_*|RCLONE_*) export "$var" ;; esac
done < /proc/1/environ

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
if ! rclone copy "$BACKUP_FILE" "gdrive:$GDRIVE_BACKUP_FOLDER_ID/" \
    --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" \
    --log-file="$LOG" \
    --log-level=INFO 2>>"$LOG"; then
    echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): ERROR - rclone upload failed" >> "$LOG"
    rm -f "$BACKUP_FILE"
    exit 1
fi

echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Uploaded $BACKUP_NAME to Google Drive" >> "$LOG"

# Clean up local backup file
rm -f "$BACKUP_FILE"

# Keep only the last 7 daily backups on Google Drive
# List all backups in reverse chronological order and delete older ones
BACKUPS=$(rclone lsf "gdrive:$GDRIVE_BACKUP_FOLDER_ID/" --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" --format=p 2>/dev/null | grep 'db-backup-.*\.sql\.gz' | sort -r)
BACKUP_COUNT=$(echo "$BACKUPS" | grep -c 'db-backup')

if [ "$BACKUP_COUNT" -gt 7 ]; then
    BACKUPS_TO_DELETE=$(echo "$BACKUPS" | tail -n +8)
    while IFS= read -r backup; do
        if [ -n "$backup" ]; then
            echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Deleting old backup: $backup" >> "$LOG"
            rclone delete "gdrive:$GDRIVE_BACKUP_FOLDER_ID/$backup" --drive-team-drive="$RCLONE_CONFIG_GDRIVE_TEAM_DRIVE" 2>>"$LOG" || true
        fi
    done <<< "$BACKUPS_TO_DELETE"
fi

echo "$(date -u '+%Y-%m-%d %H:%M:%S UTC'): Database backup completed successfully" >> "$LOG"
