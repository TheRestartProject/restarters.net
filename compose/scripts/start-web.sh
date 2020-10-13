#!/bin/bash

# We install here rather than in the Dockerfile so that we can pick up any changes made during development.
#
# Need to force reinstall of packages otherwise we see https://peterthaleikis.com/posts/how-to-fix-throw-er-unhandled-error-event.html.
npm install --global cross-env
rm -rf node_modules
npm cache clear --force
npm install --quiet

npm run watch