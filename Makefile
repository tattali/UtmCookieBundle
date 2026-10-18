all: test

phpunit:
	php vendor/bin/phpunit

lint:
	php vendor/bin/phpcs --report=code
	php vendor/bin/phpstan analyse

test: lint phpunit
