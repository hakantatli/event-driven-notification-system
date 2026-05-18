.PHONY: up down restart shell test horizon logs migrate seed setup build

SAIL = ./vendor/bin/sail

up:
	$(SAIL) up -d
	$(SAIL) artisan horizon

down:
	$(SAIL) down

restart:
	$(SAIL) down
	$(SAIL) up -d
	$(SAIL) artisan horizon

shell:
	$(SAIL) shell

test:
	$(SAIL) artisan test

horizon:
	$(SAIL) artisan horizon

logs:
	$(SAIL) logs -f

migrate:
	$(SAIL) artisan migrate

seed:
	$(SAIL) artisan db:seed

build:
	$(SAIL) build --no-cache

load-test:
	$(SAIL) artisan notifications:load-test --count=1000 --concurrency=20 --cleanup

setup:
	composer install
	cp .env.example .env
	php artisan key:generate
	$(SAIL) up -d
	$(SAIL) artisan migrate
