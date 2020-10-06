PROJECT=zubr
compose:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml up -d
compose-db:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml up -d

compose-dev-build:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml up -d --build

compose-down:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml down


compose-console:
	container=$(docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml -f infrastructure/docker-compose.db.yml ps | grep "database" | cut -d" " -f 1)

compose-up-ci:
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml up -d

compose-down-ci:
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml down

database-init-ci:
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml \
	run --no-deps --rm php bin/console database:init

compose-composer:
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml \
	run --no-deps --rm php /app/bin/composer install
