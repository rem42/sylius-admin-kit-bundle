.PHONY: up down bash console assets install test phpstan cs-fix twig-fix ci

DC = docker compose
PHP = $(DC) exec php

## Demo application (tests/Application) on http://localhost:8080/ (bootstrap)
## and http://localhost:8080/shadcn (shadcn)
up:
	$(DC) up -d --build
	@echo "\n  -> http://localhost:8080/ \n"
	@echo "\n  -> http://localhost:8080/shadcn \n"

down:
	$(DC) down

bash:
	$(PHP) bash

console:
	$(PHP) php tests/Application/bin/console $(c)

assets:
	$(PHP) php tests/Application/bin/console assets:install tests/Application/public
	$(PHP) env APP_THEME=shadcn php tests/Application/bin/console importmap:install
	$(PHP) env APP_THEME=shadcn php tests/Application/bin/console tailwind:build

## Development
install:
	$(PHP) composer install

test:
	$(PHP) vendor/bin/phpunit

phpstan:
	$(PHP) vendor/bin/phpstan analyse --memory-limit=-1

cs-fix:
	$(PHP) php vendor/bin/php-cs-fixer fix

twig-fix:
	$(PHP) php vendor/bin/twig-cs-fixer --fix

ci:
	$(PHP) php vendor/bin/php-cs-fixer fix --dry-run --diff
	$(PHP) php vendor/bin/twig-cs-fixer lint
	$(PHP) vendor/bin/phpstan analyse --memory-limit=-1
	$(PHP) vendor/bin/phpunit
