all: test

phpunit:
	php vendor/bin/phpunit

lint:
	php vendor/bin/php-cs-fixer fix --diff
	php vendor/bin/phpstan analyse

test: lint phpunit
