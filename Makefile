.DEFAULT_GOAL := help
.PHONY: help
.SILENT:

GREEN    := $(shell tput -Txterm setaf 2)
YELLOW   := $(shell tput -Txterm setaf 3)
RESET    := $(shell tput -Txterm sgr0)
COMPOSER := $(shell command -v composer 2> /dev/null)

test: install lint phpcs phpunit phpmd

## Install composer locally
composer:
ifndef COMPOSER
	curl --silent https://getcomposer.org/installer | php -- --quiet
endif

## Install dependencies
install: composer
ifndef COMPOSER
	@php composer.phar install --optimize-autoloader --prefer-dist --no-interaction
else
	@composer install --optimize-autoloader --prefer-dist --no-interaction
endif

## Run PHP unit tests
phpunit:
	@echo "${GREEN}Unit tests${RESET}"
	@php vendor/bin/phpunit

## Run PHP mess detector
phpmd:
	@echo "${GREEN}PHP Mess Detector${RESET}"
	@php vendor/bin/phpmd src/ text cleancode,codesize,naming,design,controversial,unusedcode

## Run PHP code sniffer
phpcs:
	@echo "${GREEN}PHP Code Sniffer${RESET}"
	@php vendor/bin/phpcs -p --standard=psr2 --colors src/

## PHP Parallel Lint
lint:
	@echo "${GREEN}PHP Parallel Lint${RESET}"
	@php vendor/bin/parallel-lint src/ tests/

## Fix PHP syntax with code sniffer
fix:
	@php vendor/bin/php-cs-fixer --no-interaction fix

## Generate the public API docs
docs:
	@echo "Nothing here"

## Test Coverage HTML
coverage:
	@echo "${GREEN}Tests with coverage${RESET}"
	@phpdbg -qrr vendor/bin/phpunit --coverage-html build/ --coverage-clover coverage.xml

## Prints this help
help:
	@echo "\nUsage: make ${YELLOW}<target>${RESET}\n\nThe following targets are available:\n";
	@awk -v skip=1 \
		'/^##/ { sub(/^[#[:blank:]]*/, "", $$0); doc_h=$$0; doc=""; skip=0; next } \
		 skip  { next } \
		 /^#/  { doc=doc "\n" substr($$0, 2); next } \
		 /:/   { sub(/:.*/, "", $$0); printf "\033[34m%-30s\033[0m\033[1m%s\033[0m %s\n", $$0, doc_h, doc; skip=1 }' \
		$(MAKEFILE_LIST)
