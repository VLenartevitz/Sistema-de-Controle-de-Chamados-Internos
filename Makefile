.PHONY: up down build logs shell test test-verbose coverage coverage-html coverage-min fresh seed npm-install npm-build npm-dev key migrate

up:
	docker compose up -d --build
	@echo "App: http://localhost:8000 - DB: localhost:3308"

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

# --- Cobertura de código -------------------------------------------------
# O PCOV é compilado na imagem mas vem com pcov.enabled=0 (ver Dockerfile),
# para que `make test` e as requisições web não paguem a instrumentação.
#
# A reativação usa PHP_INI_SCAN_DIR, e não `php -d`, porque `php artisan test`
# delega a execução para um processo PHP filho — que não herdaria flags de
# linha de comando. O ini que liga o driver fica em .docker/php-coverage.d/.
COVERAGE_ENV = PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/var/www/.docker/php-coverage.d
COVERAGE_DIR ?= build/coverage
COVERAGE_MIN ?= 100

# Quanto por cento do app/ está coberto por testes, arquivo por arquivo
coverage:
	docker compose exec -T -e $(COVERAGE_ENV) app php artisan test --coverage

# O mesmo, mais o relatório HTML navegável e o Clover para CI
coverage-html:
	docker compose exec -T -e $(COVERAGE_ENV) app php artisan test --coverage \
		--coverage-html=$(COVERAGE_DIR) \
		--coverage-clover=$(COVERAGE_DIR)/clover.xml
	@echo ""
	@echo "Relatório HTML: $(COVERAGE_DIR)/index.html"

# Gate: sai com status de erro se a cobertura ficar abaixo de COVERAGE_MIN
coverage-min:
	docker compose exec -T -e $(COVERAGE_ENV) app php artisan test --coverage --min=$(COVERAGE_MIN)

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
