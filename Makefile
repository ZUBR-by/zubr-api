PROJECT=zubr
compose:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml up -d
compose-dev:
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

