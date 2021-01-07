PROJECT=zubr
compose:
	COMPOSE_PROJECT_NAME=zubr \
	docker-compose -f infrastructure/docker-compose.yml -f infrastructure/docker-compose.dev.yml up -d

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
