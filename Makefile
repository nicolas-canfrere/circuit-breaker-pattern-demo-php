.DEFAULT_GOAL: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m * %s\033[0m %s\n", $$1, $$2}'

COMPOSER_HOME ?= ${HOME}/.composer
COMPOSER_SHELL = docker run -ti --rm \
	--env COMPOSER_HOME=${COMPOSER_HOME} \
	--volume ${COMPOSER_HOME}:${COMPOSER_HOME} \
	--volume ${PWD}:/app \
	--user $(shell id -u):$(shell id -g) \
	--workdir /app \
	composer:2.7.4

.PHONY: install
install: vendor ## Install dependencies

vendor: composer.json composer.lock
	$(COMPOSER_SHELL) composer install --ignore-platform-reqs
	@touch vendor

start: ## Start the application
	docker compose up -d

stop: ## Stop the application
	docker compose down

remove: ## Remove the application
	docker compose down --remove-orphans -v
	docker rmi demo-app-service1-php demo-app-service2-php
