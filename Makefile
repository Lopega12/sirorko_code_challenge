

up:
	docker compose up -d --build


up-alpine:
	docker compose -f docker-compose.yml -f docker-compose.alpine.yml up -d --build


build:
	docker compose build --no-cache


down:
	docker compose down


bash:
	docker exec -it siroko_code_challenge_php  /bin/bash

composer:
	docker compose run --rm siroko_code_challenge_php composer install


migrate:
	docker exec -it siroko_code_challenge_php php bin/console doctrine:migrations:migrate


fixtures:
	docker compose exec siroko_code_challenge_php php bin/console doctrine:fixtures:load -n || true


test:
	docker exec -it siroko_code_challenge_php php vendor/bin/phpunit  --testdox


openapi:
	docker exec -it siroko_code_challenge_php bin/console nelmio:apidoc:dump --format=yaml --no-interaction > public/docs/openapi.yaml

cs:
	docker exec -it siroko_code_challenge_php vendor/bin/php-cs-fixer fix --dry-run --diff --config=.php-cs-fixer.dist.php
fix:
	docker exec -it siroko_code_challenge_php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
lint:
	docker exec -it siroko_code_challenge_php vendor/bin/parallel-lint src tests
stan:
	docker exec -it siroko_code_challenge_php vendor/bin/phpstan analyse
md:
	docker exec -it siroko_code_challenge_php vendor/bin/phpmd ./ text ./phpmd-ruleset.xml
cpd:
	docker exec -it siroko_code_challenge_php vendor/bin/phpcpd --fuzzy --min-lines=12 --min-tokens=70 --exclude=vendor --exclude=var --exclude=tests .


messenger-consume:
	docker exec -it siroko_code_challenge_php php bin/console messenger:consume async -vv

messenger-watch:
	docker exec -it siroko_code_challenge_php php bin/console messenger:consume async --time-limit=3600 -vv


.PHONY: up up-alpine build stop sh composer migrate fixtures test openapi cs fix lint stan md cpd messenger-consume messenger-watch
