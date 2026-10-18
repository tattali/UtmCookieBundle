all: test

phpunit:
	php vendor/bin/phpunit

lint:
	php vendor/bin/phpstan analyse

test: lint phpunit
