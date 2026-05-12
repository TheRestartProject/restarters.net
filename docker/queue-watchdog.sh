#!/bin/bash
# Kills the queue worker if any job has been reserved longer than THRESHOLD seconds.
# supervisord restarts the worker automatically.
# Cron runs this every minute; logs to /var/log/queue-watchdog.log.

THRESHOLD=120
LOG=/var/log/queue-watchdog.log

# Cron does not inherit container env vars — read from the init process.
while IFS= read -r -d '' var; do
    case "$var" in DB_*) export "$var" ;; esac
done < /proc/1/environ

RESULT=$(php -r "
try {
    \$pdo = new PDO(
        'mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT') ?: 3306).';dbname='.getenv('DB_DATABASE'),
        getenv('DB_USERNAME'), getenv('DB_PASSWORD'),
        [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    \$stmt = \$pdo->query(
        'SELECT COUNT(*) AS n, MIN(payload) AS sample
           FROM jobs
          WHERE reserved_at IS NOT NULL
            AND reserved_at < UNIX_TIMESTAMP() - $THRESHOLD'
    );
    \$row = \$stmt->fetch(PDO::FETCH_ASSOC);
    if ((int)\$row['n'] > 0) {
        \$name = json_decode(\$row['sample'])->displayName ?? 'unknown';
        echo \$row['n'].':'.\$name;
    }
} catch (Exception \$e) { /* DB not ready or down — skip silently */ }
" 2>/dev/null)

if [ -n "\$RESULT" ]; then
    COUNT="\${RESULT%%:*}"
    NAME="\${RESULT#*:}"
    echo "\$(date -u '+%Y-%m-%d %H:%M:%S UTC'): \${COUNT} job(s) stuck >${THRESHOLD}s (\${NAME}) — killing worker" >> "\$LOG"
    pkill -f "artisan queue:work" 2>/dev/null || true
fi
