.PHONY: up down build logs shell test fresh seed npm-install npm-build

up:
	docker compose up -d --build
	@echo "App: http://localhost:8000 - DB: localhost:3306"

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f app

shell:
	docker compose exec app bash

test:
	docker compose exec app php artisan test --testdox

test-verbose:
	docker compose exec app php artisan test --testdox -v

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

seed:
	docker compose exec app php artisan db:seed --force

npm-install:
	docker compose exec app npm install

npm-build:
	docker compose exec app npm run build

npm-dev:
	docker compose exec app npm run dev -- --host 0.0.0.0

key:
	docker compose exec app php artisan key:generate

migrate:
	docker compose exec app php artisan migrate --force
