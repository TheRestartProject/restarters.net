#!/bin/sh
set -e

# Source is bind-mounted over /app; node_modules lives in an anonymous volume
# so the host's (possibly absent or platform-mismatched) node_modules never
# leaks in. Install when the volume is empty or the lockfile changed.
if [ ! -f node_modules/.package-lock.json ] || ! cmp -s package-lock.json node_modules/.package-lock.json; then
    echo "Installing client dependencies..."
    npm ci
    cp package-lock.json node_modules/.package-lock.json
fi

if [ "$NUXT_DEV_MODE" = "true" ]; then
    exec npm run dev -- --host 0.0.0.0 --port 3000
else
    npm run build
    exec node .output/server/index.mjs
fi
