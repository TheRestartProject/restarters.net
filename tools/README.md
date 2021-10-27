# Tools

## build-dev

For (re)building dev instances.

Simple usage to run all steps (apart from -d):

`./tools/build-dev.sh -a`

Other options:

- d : delete vendor and node folders
- i : install dependencies
- b : build assets
- c : clear caches
- m : migrate db

## php-cs-fixer

Currently using v2, and the set of rules as used by Laravel Shift: https://gist.github.com/laravel-shift/cab527923ed2a109dda047b97d53c200

### Usage

To run on all Laravel-related files:

`./tools/php-cs-fixer.phar fix`

To run on a subset of filesapp/Category.php app/Device.php etc etc

`./tools/php-cs-fixer.phar fix --config=.php-cs-fixer.dist.php app/Category.php app/Device.php etc etc`
