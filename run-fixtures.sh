#!/bin/sh
# Single-token wrapper: task docker:run:bash wraps its argument in quotes,
# so a multi-word command becomes one command name. Call this instead.
exec php artisan tinker parity-fixtures.php
