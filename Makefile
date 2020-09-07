PROJECT=zubr
compose:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml up -d

