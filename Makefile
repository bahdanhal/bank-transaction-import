.PHONY: setup tests test phpstan cs cs-fix build app

setup:
	composer run setup

app:
	php artisan serve

build:
	npm run build

tests:
	php artisan test

test: tests

phpstan:
	./vendor/bin/phpstan

cs:
	./vendor/bin/phpcs

cs-fix:
	./vendor/bin/phpcbf
