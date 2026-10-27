#!/bin/bash
#
# Upload coverage to Coveralls with the official reporter.
#
#   scripts/coveralls.sh report <flag> <format> <file> [base-path]
#   scripts/coveralls.sh done
#
# The PHP (build) and client (build-client) jobs each upload a parallel job
# flagged php / client; the coverage-done job then closes the Coveralls build.
# All jobs in one CircleCI workflow share the workflow id as the build number.
#
# Coverage is only generated on develop (see docker:test:phpunit), so this
# skips (exit 0) on other branches and when COVERALLS_REPO_TOKEN is not set
# (local runs, forked PRs). It fails if Coveralls rejects the upload.

set -euo pipefail

VERSION=v0.6.22

if [ -z "${COVERALLS_REPO_TOKEN:-}" ]; then
    echo "COVERALLS_REPO_TOKEN not set, skipping Coveralls"
    exit 0
fi

if [ -n "${CIRCLE_BRANCH:-}" ] && [ "$CIRCLE_BRANCH" != "develop" ]; then
    echo "Coverage is only uploaded for develop, skipping Coveralls"
    exit 0
fi

tmp=$(mktemp -d)
curl -fsSL "https://github.com/coverallsapp/coverage-reporter/releases/download/$VERSION/coveralls-linux.tar.gz" | tar -xz -C "$tmp"

build=()
if [ -n "${CIRCLE_WORKFLOW_ID:-}" ]; then
    build=(--build-number "$CIRCLE_WORKFLOW_ID")
fi

case "${1:-}" in
    report)
        flag=$2 format=$3 file=$4 base=${5:-}
        test -s "$file" || { echo "$file not found"; exit 1; }
        if [ "$format" = "clover" ]; then
            # PHPUnit runs in the container, so its paths start /var/www/.
            sed 's#name="/var/www/#name="#g' "$file" > "$tmp/clover.xml"
            file=$tmp/clover.xml
        fi
        args=(report "$file" --format="$format" --job-flag="$flag" --parallel --no-logo "${build[@]}")
        if [ -n "$base" ]; then
            args+=(--base-path="$base")
        fi
        "$tmp/coveralls" "${args[@]}"
        ;;
    done)
        "$tmp/coveralls" done --no-logo "${build[@]}"
        ;;
    *)
        echo "Usage: $0 report <flag> <format> <file> [base-path] | done"
        exit 1
        ;;
esac
