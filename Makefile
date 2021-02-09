PROJECT=zubr
# --user "$$(id -u):$$(id -g)"
compose:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml \
	-f infrastructure/docker-compose.dev.yml up -d

compose-composer:
	docker exec zubr_php_1 composer install

compose-composer-ci:
	docker exec --user "$$(id -u):$$(id -g)" zubr_php_1 composer install -a --no-dev --no-interaction

compose-console:
	docker exec --user "$$(id -u):$$(id -g)" zubr_php_1 bin/console $(COMMAND)

compose-up-ci:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml \
	-f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml \
	up -d

compose-down-ci:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml \
	-f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml \
	down

compose-dev-build:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml  build

compose-down:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml \
	-f infrastructure/docker-compose.db.yml down

# update-alternatives --install /usr/bin/python python /usr/bin/python3 1
# update-alternatives --install /usr/bin/python python /usr/bin/python2 2

backends:
	ansible-playbook -i infrastructure/hosts_all \
		-u root infrastructure/backends.yaml -v -K

frontends:
	ansible-playbook -i infrastructure/hosts_all \
		-u root infrastructure/frontends.yaml -v -K

prepare_deploy:
	ansible-playbook -i infrastructure/hosts_all \
		-u root infrastructure/deploy_only.yaml -v -K


generate_content:
	php bin/console generate:content
