.PHONY: up bash test phpstan cs cs\:fix

DOCKER_RUN := docker compose run --rm --no-deps shipmonk-packing-app

up:
	docker compose up -d

bash:
	docker compose run --rm shipmonk-packing-app bash

test:
	$(DOCKER_RUN) vendor/bin/phpunit tests

phpstan:
	$(DOCKER_RUN) vendor/bin/phpstan analyse src --level max

cs:
	$(DOCKER_RUN) vendor/bin/phpcs --standard=PSR12 src

cs\:fix:
	$(DOCKER_RUN) vendor/bin/phpcbf --standard=PSR12 src
