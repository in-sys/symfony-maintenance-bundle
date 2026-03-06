UID ?= $(shell id -u)
GID ?= $(shell id -g)
DOCKER_COMPOSE = UID=$(UID) GID=$(GID) docker compose
RUN_PHP = $(DOCKER_COMPOSE) run --rm php

.PHONY: docker-build composer-update test phpstan shell

docker-build:
	$(DOCKER_COMPOSE) build php

composer-update:
	$(RUN_PHP) composer update --prefer-dist

composer-install:
	$(RUN_PHP) composer install --prefer-dist

test:
	$(RUN_PHP) vendor/bin/phpunit

phpstan:
	$(RUN_PHP) vendor/bin/phpstan analyse --no-progress --memory-limit=512M

shell:
	$(RUN_PHP) bash
