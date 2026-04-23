#!/bin/bash
#
# Migrate production Restarters.net data to Fly.io.
#
# This is a self-contained script designed to be copied onto the production
# server (restart-sp, app root /srv/users/serverpilot/apps/restarters).
# It has zero dependencies on the Laravel codebase — just standard CLI tools.
#
# Three phases (each can be run independently):
#   1. Secrets  — Read production .env, set values as Fly secrets
#   2. Database — mysqldump production DB, import into Fly MySQL via proxy
#   3. Images   — aws s3 sync of public/uploads/ to Tigris bucket
#
# Prerequisites:
#   - flyctl installed and authenticated (or FLY_ACCESS_TOKEN in .env)
#   - mysql + mysqldump available (for --db-only / full run)
#   - aws cli installed (for --images-only / full run)
#
# Usage:
#   ./fly-migrate.sh [OPTIONS]
#
# Options:
#   --secrets-only           Only set Fly secrets from .env
#   --db-only                Only migrate the database
#   --images-only            Only sync images to Tigris
#   --dry-run                Show what would be done without executing
#   --fly-db-password PASS   Fly MySQL password (skips auto-detection)
#   --dump-file PATH         Use existing dump file (skip mysqldump step)
#   --uploads-dir PATH       Uploads directory (default: ./public/uploads)
#   --env-file PATH          Path to .env file (default: .env)
#   -h, --help               Show this help message
#
set -euo pipefail

# ─── Constants ────────────────────────────────────────────────────────────────

FLY_APP="restarters"
FLY_DB_APP="restarters-db"
FLY_DB_NAME="restarters"
LOCAL_PROXY_PORT=13306

# ─── Defaults ─────────────────────────────────────────────────────────────────

ENV_FILE=".env"
UPLOADS_DIR="./public/uploads"
DO_SECRETS=true
DO_DB=true
DO_IMAGES=true
DRY_RUN=false
FLY_DB_PASSWORD=""
DUMP_FILE=""
PROXY_PID=""

# ─── Argument parsing ────────────────────────────────────────────────────────

usage() {
    sed -n '/^# Usage:/,/^#$/p' "$0" | sed 's/^# \?//'
    sed -n '/^# Options:/,/^#$/p' "$0" | sed 's/^# \?//'
    exit 0
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --secrets-only)   DO_DB=false; DO_IMAGES=false; shift ;;
        --db-only)        DO_SECRETS=false; DO_IMAGES=false; shift ;;
        --images-only)    DO_SECRETS=false; DO_DB=false; shift ;;
        --dry-run)        DRY_RUN=true; shift ;;
        --fly-db-password) FLY_DB_PASSWORD="$2"; shift 2 ;;
        --dump-file)      DUMP_FILE="$2"; shift 2 ;;
        --uploads-dir)    UPLOADS_DIR="$2"; shift 2 ;;
        --env-file)       ENV_FILE="$2"; shift 2 ;;
        -h|--help)        usage ;;
        *) echo "Unknown option: $1"; usage ;;
    esac
done

# ─── Helper functions ─────────────────────────────────────────────────────────

RED='\033[0;31m'
YELLOW='\033[0;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

log_info()  { echo -e "${GREEN}==>${NC} $*"; }
log_warn()  { echo -e "${YELLOW}WARNING:${NC} $*"; }
log_err()   { echo -e "${RED}ERROR:${NC} $*" >&2; }
log_step()  { echo -e "${CYAN}  ->$NC $*"; }
log_dry()   { echo -e "${YELLOW}[DRY RUN]${NC} $*"; }

# Read a value from .env file. Handles double-quoted, single-quoted, and unquoted values.
env_val() {
    local key="$1"
    local line
    line=$(grep -E "^${key}=" "$ENV_FILE" 2>/dev/null | head -1) || true
    if [[ -z "$line" ]]; then
        return
    fi
    local val="${line#*=}"
    # Strip surrounding quotes
    val="${val#\"}"
    val="${val%\"}"
    val="${val#\'}"
    val="${val%\'}"
    echo "$val"
}

# Check that a CLI tool is available
require_tool() {
    local tool="$1"
    local purpose="${2:-}"
    if ! command -v "$tool" &>/dev/null; then
        log_err "'$tool' is required${purpose:+ for $purpose} but not found in PATH."
        exit 1
    fi
}

# Run a command, or just print it in dry-run mode
run() {
    if [[ "$DRY_RUN" = true ]]; then
        log_dry "$*"
    else
        "$@"
    fi
}

# ─── Cleanup trap ─────────────────────────────────────────────────────────────

cleanup() {
    if [[ -n "$PROXY_PID" ]]; then
        log_info "Cleaning up: killing proxy (PID $PROXY_PID)..."
        kill "$PROXY_PID" 2>/dev/null || true
        wait "$PROXY_PID" 2>/dev/null || true
        PROXY_PID=""
    fi
}

trap cleanup EXIT

# ─── Prerequisite checks ─────────────────────────────────────────────────────

if [[ ! -f "$ENV_FILE" ]]; then
    log_err ".env file not found at: $ENV_FILE"
    echo "  Run this script from the application root, or use --env-file PATH"
    exit 1
fi

if [[ "$DRY_RUN" = false ]]; then
    require_tool fly "Fly.io operations"

    if [[ "$DO_DB" = true ]]; then
        if [[ -z "$DUMP_FILE" ]]; then
            require_tool mysqldump "database export"
        fi
        require_tool mysql "database import"
    fi

    if [[ "$DO_IMAGES" = true ]]; then
        require_tool aws "Tigris S3 sync"
    fi
else
    # In dry-run, only require fly for auth check
    require_tool fly "Fly.io operations"
fi

# ─── Fly authentication ──────────────────────────────────────────────────────

# If FLY_API_TOKEN is not already set, try to read FLY_ACCESS_TOKEN from .env
if [[ -z "${FLY_API_TOKEN:-}" ]]; then
    FLY_TOKEN=$(env_val FLY_ACCESS_TOKEN)
    if [[ -n "$FLY_TOKEN" ]]; then
        export FLY_API_TOKEN="$FLY_TOKEN"
        log_info "Using FLY_ACCESS_TOKEN from .env for Fly authentication"
    fi
fi

# Verify fly auth works (skip in dry-run mode)
if [[ "$DRY_RUN" = false ]]; then
    if ! fly auth whoami &>/dev/null; then
        log_err "Not authenticated with Fly.io. Either:"
        echo "  - Run 'fly auth login' first, or"
        echo "  - Set FLY_ACCESS_TOKEN in your .env file"
        exit 1
    fi
else
    if fly auth whoami &>/dev/null; then
        log_step "Fly auth: $(fly auth whoami 2>/dev/null)"
    else
        log_warn "Not authenticated with Fly.io (dry-run continues anyway)"
    fi
fi

if [[ "$DRY_RUN" = true ]]; then
    echo ""
    log_info "DRY RUN MODE — no changes will be made"
    echo ""
fi

# ─── Phase 1: Set Fly secrets from production .env ───────────────────────────

if [[ "$DO_SECRETS" = true ]]; then
    log_info "Phase 1: Setting Fly.io secrets from ${ENV_FILE}..."

    # Keys to extract from production .env and set as Fly secrets.
    # Non-secret config (TBL_*, DEVICE_*, etc.) goes in fly.toml [env].
    SECRET_KEYS=(
        APP_KEY
        # DB_USERNAME and DB_PASSWORD are NOT imported — Fly DB has its own credentials
        DISCOURSE_URL
        DISCOURSE_SECRET
        DISCOURSE_APIUSER
        DISCOURSE_APIKEY
        WIKI_URL
        WIKI_DB
        WIKI_USER
        WIKI_PASSWORD
        WIKI_APIUSER
        WIKI_APIPASSWORD
        WIKI_COOKIE_PREFIX
        WIKI_HOST
        SENTRY_LARAVEL_DSN
        MAPBOX_TOKEN
        GOOGLE_API_CONSOLE_KEY
        GOOGLE_ANALYTICS_TRACKING_ID
        GOOGLE_TAG_MANAGER_ID
        CALENDAR_HASH
        MAIL_MAILER
        MAIL_HOST
        MAIL_PORT
        MAIL_USERNAME
        MAIL_PASSWORD
        MAIL_ENCRYPTION
        MAIL_FROM_ADDRESS
        MAIL_FROM_NAME
        WP_XMLRPC_ENDPOINT
        WP_XMLRPC_USER
        WP_XMLRPC_PSWD
        AWS_ACCESS_KEY_ID
        AWS_SECRET_ACCESS_KEY
        AWS_BUCKET
        MAILGUN_DOMAIN
        MAILGUN_SECRET
        MAILGUN_ENDPOINT
        DRIP_API_TOKEN
        DRIP_ACCOUNT_ID
        DRIP_CAMPAIGN_ID
        SEND_COMMAND_LOGS_TO
        SUPPORT_EMAIL_ADDRESS
        REPAIRDIRECTORY_URL
    )

    # Build KEY=VALUE lines for fly secrets import (handles values with spaces)
    SECRET_COUNT=0
    SECRETS_PAYLOAD=""
    for key in "${SECRET_KEYS[@]}"; do
        val=$(env_val "$key")
        if [[ -n "$val" ]]; then
            SECRETS_PAYLOAD+="${key}=${val}"$'\n'
            SECRET_COUNT=$((SECRET_COUNT + 1))
        fi
    done

    if [[ $SECRET_COUNT -gt 0 ]]; then
        log_step "Setting $SECRET_COUNT secrets on ${FLY_APP}..."
        if [[ "$DRY_RUN" = true ]]; then
            log_dry "echo '<${SECRET_COUNT} secrets>' | fly secrets import -a ${FLY_APP}"
        else
            echo "$SECRETS_PAYLOAD" | fly secrets import -a "$FLY_APP"
        fi
        log_step "App secrets set."

        # Also set MySQL password on the DB app so it matches
        DB_PASS=$(env_val DB_PASSWORD)
        if [[ -n "$DB_PASS" ]]; then
            log_step "Setting MYSQL_PASSWORD and MYSQL_ROOT_PASSWORD on ${FLY_DB_APP}..."
            if [[ "$DRY_RUN" = true ]]; then
                log_dry "echo 'MYSQL_PASSWORD=***\nMYSQL_ROOT_PASSWORD=***' | fly secrets import -a ${FLY_DB_APP}"
            else
                printf "MYSQL_PASSWORD=%s\nMYSQL_ROOT_PASSWORD=%s\n" "$DB_PASS" "$DB_PASS" \
                    | fly secrets import -a "$FLY_DB_APP"
            fi
            log_step "DB secrets set."
        fi
    else
        log_warn "No secrets found in ${ENV_FILE}"
    fi

    echo ""
fi

# ─── Phase 2: Database migration ─────────────────────────────────────────────

if [[ "$DO_DB" = true ]]; then
    log_info "Phase 2: Database migration"

    LIVE_DB_HOST=$(env_val DB_HOST)
    LIVE_DB_USER=$(env_val DB_USERNAME)
    LIVE_DB_PASS=$(env_val DB_PASSWORD)
    LIVE_DB_NAME=$(env_val DB_DATABASE)

    # Step 2a: Export database (unless --dump-file was provided)
    if [[ -n "$DUMP_FILE" ]]; then
        if [[ ! -f "$DUMP_FILE" ]]; then
            log_err "Dump file not found: $DUMP_FILE"
            exit 1
        fi
        log_step "Using existing dump file: $DUMP_FILE"
    else
        DUMP_FILE="/tmp/restarters-dump-$(date +%Y%m%d-%H%M%S).sql"
        log_step "Exporting database '${LIVE_DB_NAME}' from ${LIVE_DB_HOST}..."

        # Build mysqldump flags — only add flags if the local mysqldump supports them
        DUMP_EXTRA_FLAGS=()
        MYSQLDUMP_HELP=$(mysqldump --help 2>&1)
        if echo "$MYSQLDUMP_HELP" | grep -q 'column-statistics'; then
            DUMP_EXTRA_FLAGS+=(--column-statistics=0)
        fi
        if echo "$MYSQLDUMP_HELP" | grep -q 'set-gtid-purged'; then
            DUMP_EXTRA_FLAGS+=(--set-gtid-purged=OFF)
        fi
        if echo "$MYSQLDUMP_HELP" | grep -q 'no-tablespaces'; then
            DUMP_EXTRA_FLAGS+=(--no-tablespaces)
        fi

        if [[ "$DRY_RUN" = true ]]; then
            log_dry "mysqldump -h ${LIVE_DB_HOST} -u ${LIVE_DB_USER} --single-transaction ${DUMP_EXTRA_FLAGS[*]+"${DUMP_EXTRA_FLAGS[*]}"} --routines --triggers ${LIVE_DB_NAME} > ${DUMP_FILE}"
        else
            mysqldump \
                -h "$LIVE_DB_HOST" \
                -u "$LIVE_DB_USER" \
                -p"$LIVE_DB_PASS" \
                --single-transaction \
                ${DUMP_EXTRA_FLAGS[@]+"${DUMP_EXTRA_FLAGS[@]}"} \
                --routines \
                --triggers \
                "$LIVE_DB_NAME" > "$DUMP_FILE"

            log_step "Exported to ${DUMP_FILE} ($(du -h "$DUMP_FILE" | cut -f1))"
        fi
    fi

    # Step 2b: Strip DEFINER clauses (they reference production users that don't exist on Fly)
    if [[ "$DRY_RUN" = false ]]; then
        log_step "Stripping DEFINER clauses from dump..."
        sed -i 's/DEFINER=[^ ]* / /g' "$DUMP_FILE"
    else
        log_dry "sed -i 's/DEFINER=[^ ]* / /g' ${DUMP_FILE}"
    fi

    # Step 2c: Get Fly DB password
    if [[ -z "$FLY_DB_PASSWORD" ]]; then
        log_step "Retrieving Fly DB password..."
        if [[ "$DRY_RUN" = true ]]; then
            log_dry "fly ssh console -a ${FLY_DB_APP} -C 'printenv MYSQL_PASSWORD'"
            FLY_DB_PASSWORD="<dry-run-placeholder>"
        else
            FLY_DB_PASSWORD=$(fly ssh console -a "$FLY_DB_APP" -C "printenv MYSQL_PASSWORD" 2>/dev/null | tr -d '[:space:]') || true
            if [[ -z "$FLY_DB_PASSWORD" ]]; then
                log_warn "Could not retrieve password automatically."
                echo -n "  Enter Fly MySQL password for user 'restarters': "
                read -rs FLY_DB_PASSWORD
                echo ""
                if [[ -z "$FLY_DB_PASSWORD" ]]; then
                    log_err "No password provided. Aborting."
                    exit 1
                fi
            fi
        fi
    fi

    # Step 2d: Start fly proxy and wait for it to be ready
    log_step "Starting Fly MySQL proxy on localhost:${LOCAL_PROXY_PORT}..."

    if [[ "$DRY_RUN" = true ]]; then
        log_dry "fly proxy ${LOCAL_PROXY_PORT}:3306 -a ${FLY_DB_APP} &"
        log_dry "mysql -h 127.0.0.1 -P ${LOCAL_PROXY_PORT} -u restarters ${FLY_DB_NAME} < ${DUMP_FILE}"
        log_dry "fly ssh console -a ${FLY_DB_APP} -C 'mysql -u root -e \"GRANT ALL ON ${FLY_DB_NAME}.* TO restarters@%\"'"
    else
        fly proxy "${LOCAL_PROXY_PORT}:3306" -a "$FLY_DB_APP" &
        PROXY_PID=$!

        # Poll until the proxy is accepting connections (up to 30 seconds)
        log_step "Waiting for proxy to be ready..."
        WAITED=0
        MAX_WAIT=30
        while ! mysql -h 127.0.0.1 -P "$LOCAL_PROXY_PORT" -u restarters -p"$FLY_DB_PASSWORD" -e "SELECT 1" &>/dev/null; do
            if ! kill -0 "$PROXY_PID" 2>/dev/null; then
                log_err "Fly proxy process died unexpectedly."
                PROXY_PID=""
                exit 1
            fi
            if [[ $WAITED -ge $MAX_WAIT ]]; then
                log_err "Proxy did not become ready within ${MAX_WAIT}s."
                exit 1
            fi
            sleep 1
            WAITED=$((WAITED + 1))
        done
        log_step "Proxy ready (took ${WAITED}s)."

        # Step 2e: Drop and recreate database for idempotent re-runs
        log_step "Dropping and recreating database '${FLY_DB_NAME}' for clean import..."
        fly ssh console -a "$FLY_DB_APP" -C \
            "mysql -u root -e \"DROP DATABASE IF EXISTS \\\`${FLY_DB_NAME}\\\`; CREATE DATABASE \\\`${FLY_DB_NAME}\\\`;\""

        # Step 2f: Import the dump
        log_step "Importing database into Fly MySQL (this may take a while)..."
        mysql \
            -h 127.0.0.1 \
            -P "$LOCAL_PROXY_PORT" \
            -u restarters \
            -p"$FLY_DB_PASSWORD" \
            --max-allowed-packet=64M \
            "$FLY_DB_NAME" < "$DUMP_FILE"

        log_step "Database imported."

        # Step 2f: Grant privileges via fly ssh (avoids needing root password over proxy)
        log_step "Granting privileges to 'restarters' user..."
        fly ssh console -a "$FLY_DB_APP" -C \
            "mysql -u root -e \"GRANT ALL ON \\\`${FLY_DB_NAME}\\\`.* TO 'restarters'@'%'; FLUSH PRIVILEGES;\""

        log_step "Privileges granted."

        # Step 2g: Kill proxy (cleanup trap will also handle this)
        kill "$PROXY_PID" 2>/dev/null || true
        wait "$PROXY_PID" 2>/dev/null || true
        PROXY_PID=""
        log_step "Proxy stopped."
    fi

    echo ""
fi

# ─── Phase 3: Upload images to Tigris ────────────────────────────────────────

if [[ "$DO_IMAGES" = true ]]; then
    log_info "Phase 3: Syncing images to Tigris"

    BUCKET=$(env_val AWS_BUCKET)

    if [[ -z "$BUCKET" ]]; then
        if [[ "$DRY_RUN" = true ]]; then
            log_warn "AWS_BUCKET not set in ${ENV_FILE} (would fail in real run)"
            BUCKET="<unset>"
        else
            log_err "AWS_BUCKET not set in ${ENV_FILE}"
            exit 1
        fi
    fi

    if [[ ! -d "$UPLOADS_DIR" ]]; then
        if [[ "$DRY_RUN" = true ]]; then
            log_warn "Uploads directory not found at ${UPLOADS_DIR} (would fail in real run)"
        else
            log_err "Uploads directory not found at ${UPLOADS_DIR}"
            exit 1
        fi
    fi

    # Export AWS credentials from .env for the aws CLI
    export AWS_ACCESS_KEY_ID=$(env_val AWS_ACCESS_KEY_ID)
    export AWS_SECRET_ACCESS_KEY=$(env_val AWS_SECRET_ACCESS_KEY)

    if [[ -z "$AWS_ACCESS_KEY_ID" || -z "$AWS_SECRET_ACCESS_KEY" ]]; then
        if [[ "$DRY_RUN" = true ]]; then
            log_warn "AWS credentials not set in ${ENV_FILE} (would fail in real run)"
        else
            log_err "AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY must be set in ${ENV_FILE}"
            exit 1
        fi
    fi

    if [[ -d "$UPLOADS_DIR" ]]; then
        FILE_COUNT=$(find "$UPLOADS_DIR" -type f | wc -l)
        log_step "Source: ${UPLOADS_DIR} (${FILE_COUNT} files)"
    else
        log_step "Source: ${UPLOADS_DIR}"
    fi
    log_step "Destination: s3://${BUCKET}/"

    if [[ "$DRY_RUN" = true ]]; then
        log_dry "aws s3 sync ${UPLOADS_DIR} s3://${BUCKET}/ --endpoint-url https://fly.storage.tigris.dev --size-only"
    else
        UPLOADED=0
        aws s3 sync "$UPLOADS_DIR" "s3://${BUCKET}/" \
            --endpoint-url https://fly.storage.tigris.dev \
            --size-only 2>&1 | while IFS= read -r line; do
            UPLOADED=$((UPLOADED + 1))
            if [[ $FILE_COUNT -gt 0 ]]; then
                PCT=$((UPLOADED * 100 / FILE_COUNT))
                printf "\r  -> Progress: %d/%d files (%d%%)" "$UPLOADED" "$FILE_COUNT" "$PCT"
            else
                printf "\r  -> Uploaded: %d files" "$UPLOADED"
            fi
        done
        echo ""

        log_step "Image sync complete."
    fi

    echo ""
fi

# ─── Summary ──────────────────────────────────────────────────────────────────

echo ""
log_info "Migration complete!"
echo ""
echo "Next steps:"
echo "  1. Deploy the app:       fly deploy -a ${FLY_APP}"
echo "  2. Run migrations:       fly ssh console -a ${FLY_APP} -C 'php artisan migrate --force'"
echo "  3. Verify at:            https://${FLY_APP}.fly.dev"
echo "  4. Check logs:           fly logs -a ${FLY_APP}"
echo "  5. Verify images load:   https://${FLY_APP}.fly.dev/uploads/"
