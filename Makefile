.PHONY: up bash test phpstan cs cs\:fix

DOCKER_RUN := docker compose run --rm --no-deps shipmonk-packing-app

up:
	docker compose up -d

bash:
	docker compose run --rm shipmonk-packing-app bash

test:
	docker compose up -d --wait shipmonk-packing-test-mysql
	$(DOCKER_RUN) vendor/bin/phpunit tests

phpstan:
	$(DOCKER_RUN) php -d memory_limit=2G vendor/bin/phpstan analyse src --level max

cs:
	$(DOCKER_RUN) vendor/bin/phpcs --standard=PSR12 src tests

cs\:fix:
	$(DOCKER_RUN) vendor/bin/phpcbf --standard=PSR12 src tests
