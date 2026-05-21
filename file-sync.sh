#!/bin/bash
# File sync script for Restarters.net Docker development
# Monitors WSL filesystem changes and syncs to Docker containers
# Adapted from FreegleDocker for Restarters.net

PROJECT_DIR="/home/edward/restarters.net"
CONTAINER_NAME="restarters"

echo "Starting Restarters.net file sync monitor..."
echo "Project: $PROJECT_DIR"
echo "Press Ctrl+C to stop"
echo ""

# Function to determine target container
get_container_info() {
    local file_path="$1"
    local relative_path="${file_path#$PROJECT_DIR/}"

    # Main application container
    echo "${CONTAINER_NAME} /var/www/${relative_path} App"
}

# Function to sync file
sync_file() {
    local file_path="$1"
    local event_type="$2"

    # Skip certain files and directories
    if [[ "$file_path" == */.git/* ]] || \
       [[ "$file_path" == */node_modules/* ]] || \
       [[ "$file_path" == */vendor/* ]] || \
       [[ "$file_path" == */.idea/* ]] || \
       [[ "$file_path" == */storage/logs/* ]] || \
       [[ "$file_path" == */storage/framework/* ]] || \
       [[ "$file_path" == */.DS_Store ]] || \
       [[ "$file_path" == */*.log ]] || \
       [[ "$file_path" == */file-sync.sh ]]; then
        return
    fi

    # Get container info
    local container_info=$(get_container_info "$file_path")

    if [ -n "$container_info" ]; then
        while IFS= read -r line; do
            local container=$(echo "$line" | cut -d' ' -f1)
            local dest_path=$(echo "$line" | cut -d' ' -f2)
            local label=$(echo "$line" | cut -d' ' -f3-)

            # Check if container is running
            if docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
                if [ "$event_type" == "DELETE" ] || [ ! -f "$file_path" ]; then
                    # Handle deletion
                    docker exec "$container" rm -f "$dest_path" 2>/dev/null
                    echo "[$(date '+%H:%M:%S')] [$label] Deleted: ${dest_path#/var/www/}"
                else
                    # Handle creation/modification
                    docker cp "$file_path" "$container:$dest_path" 2>/dev/null
                    if [ $? -eq 0 ]; then
                        echo "[$(date '+%H:%M:%S')] [$label] Synced: ${dest_path#/var/www/}"

                        # Clear Laravel caches if needed
                        if [[ "$file_path" == *.php ]] && [[ "$file_path" == */config/* ]]; then
                            docker exec "$container" php artisan config:cache 2>/dev/null
                        fi
                        if [[ "$file_path" == *.php ]] && [[ "$file_path" == */routes/* ]]; then
                            docker exec "$container" php artisan route:cache 2>/dev/null
                        fi
                    fi
                fi
            fi
        done <<< "$container_info"
    fi
}

# Function to perform initial sync
initial_sync() {
    echo "Performing initial sync..."
    echo ""

    # Sync key directories
    for dir in app config database resources routes tests lang public/css public/js; do
        if [ -d "$PROJECT_DIR/$dir" ]; then
            echo "Syncing $dir..."
            find "$PROJECT_DIR/$dir" -type f -not -path "*/node_modules/*" -not -path "*/.git/*" | while read -r file; do
                sync_file "$file" "INITIAL"
            done
        fi
    done

    # Sync key files
    for file in .env composer.json package.json webpack.mix.js; do
        if [ -f "$PROJECT_DIR/$file" ]; then
            echo "Syncing $file..."
            sync_file "$PROJECT_DIR/$file" "INITIAL"
        fi
    done

    echo ""
    echo "Initial sync complete!"
    echo ""
}

# Check if containers are running
check_containers() {
    local app_running=false

    if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        app_running=true
    fi

    if [ "$app_running" = false ]; then
        echo "Error: No Restarters containers are running!"
        echo "Please start your Docker containers with: docker-compose up -d"
        exit 1
    fi

    echo "Found running containers:"
    docker ps --format 'table {{.Names}}\t{{.Status}}' | grep "^restarters"
    echo ""
}

# Main monitoring loop
monitor_changes() {
    echo "Monitoring for file changes..."
    echo ""

    inotifywait -mr --format '%w%f %e' \
        -e modify -e create -e delete -e moved_to -e moved_from \
        --exclude '(\.git|node_modules|vendor|storage/logs|storage/framework|\.idea|\.DS_Store|.*\.log|file-sync\.sh)' \
        "$PROJECT_DIR" | while read file event; do

        # Extract file path and event type
        file_path=$(echo "$file" | cut -d' ' -f1)
        event_type=$(echo "$file" | cut -d' ' -f2)

        # Determine event type
        if [[ "$event_type" == *DELETE* ]] || [[ "$event_type" == *MOVED_FROM* ]]; then
            sync_file "$file_path" "DELETE"
        else
            sync_file "$file_path" "$event_type"
        fi
    done
}

# Trap Ctrl+C
trap 'echo -e "\n\nFile sync stopped."; exit 0' INT

# Main execution
echo "==================================="
echo "Restarters.net Docker File Sync"
echo "==================================="
echo ""

# Check for inotifywait
if ! command -v inotifywait &> /dev/null; then
    echo "Error: inotifywait is not installed!"
    echo "Please install it with: sudo apt-get install inotify-tools"
    exit 1
fi

# Check containers
check_containers

# Ask about initial sync
read -p "Perform initial sync? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    initial_sync
fi

# Start monitoring
monitor_changes