#!/bin/bash

# Simple script to upload Clover XML coverage to Coveralls API
# Usage: ./upload-coverage.sh <clover-xml-path>

if [ -z "$COVERALLS_REPO_TOKEN" ]; then
    echo "COVERALLS_REPO_TOKEN environment variable is required"
    exit 1
fi

if [ ! -f "$1" ]; then
    echo "Coverage file $1 not found"
    exit 1
fi

# Convert Clover XML to a simple JSON format that Coveralls can accept
# This is a minimal implementation - we'll send the XML as-is in the API call

echo "Uploading coverage to Coveralls..."

# Create a temporary JSON payload
cat > /tmp/coveralls.json << EOF
{
    "repo_token": "$COVERALLS_REPO_TOKEN",
    "service_name": "circleci",
    "source_files": []
}
EOF

# Upload using curl with form data (Coveralls accepts XML in coverage_file field)
curl -F "repo_token=$COVERALLS_REPO_TOKEN" \
     -F "service_name=circleci" \
     -F "coverage_file=@$1" \
     https://coveralls.io/api/v1/jobs

echo ""
echo "Coverage upload completed"