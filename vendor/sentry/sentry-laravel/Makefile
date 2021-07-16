.PHONY: test

develop: update-submodules
	composer install --dev
	make setup-git

update-submodules:
	git submodule init
	git submodule update

cs:
	vendor/bin/php-cs-fixer fix --config=.php_cs --verbose --diff

cs-dry-run:
	vendor/bin/php-cs-fixer fix --config=.php_cs --verbose --diff --dry-run

test:
	vendor/bin/phpunit

setup-git:
	git config branch.autosetuprebase always
