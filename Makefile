up:
	docker compose up -d

down:
	docker compose down

sh:
	docker compose exec php-fpm sh

test:
	docker compose exec -e XDEBUG_MODE=off php-fpm composer test

migrate:
	docker compose exec php-fpm php bin/console doctrine:migrations:migrate -n

lint:
	docker compose exec php-fpm php bin/console lint:twig templates/
	docker compose exec php-fpm php bin/console lint:yaml config/
	docker compose exec php-fpm php bin/console doctrine:schema:validate
