SHELL=bash

DOCKER_COMPOSE  = docker-compose

EXEC_JS         = node
EXEC_DB         = $(DOCKER_COMPOSE) exec -T mariadb
EXEC_QA         = $(DOCKER_COMPOSE) run -T -e APP_ENV=test --rm symfony
EXEC_PHP        = $(DOCKER_COMPOSE) exec symfony docker-php-entrypoint
RUN_PHP         = $(DOCKER_COMPOSE) run --entrypoint docker-php-entrypoint symfony

SYMFONY_CONSOLE = $(EXEC_PHP) php bin/console
COMPOSER        = $(RUN_PHP) composer
YARN            = yarn

DB_USER = root
DB_PWD = root

CURRENT_DATE = `date "+%Y-%m-%d_%H-%M-%S"`

# Helper variables
_TITLE := "\033[32m[%s]\033[0m %s\n" # Green text
_ERROR := "\033[31m[%s]\033[0m %s\n" # Red text

##
## General purpose commands
## ────────────────────────
##

.DEFAULT_GOAL := help
help: ## Show this help message
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf " \033[32m%-25s\033[0m%s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
.PHONY: help

install: docker-compose.override.yml build node_modules start vendor db test-db assets ## Install the project

docker-compose.override.yml:
	cp docker-compose.override.yml.dist docker-compose.override.yml

start: ## Start all containers
	@$(DOCKER_COMPOSE) up -d --remove-orphans --no-recreate
.PHONY: start

stop: ## Stop all containers
	@$(DOCKER_COMPOSE) stop
.PHONY: stop

build:
	@$(DOCKER_COMPOSE) pull --include-deps
	@$(DOCKER_COMPOSE) build --force-rm --compress
.PHONY: build

vendor: ## Install PHP vendors
	$(COMPOSER) install
.PHONY: vendor

node_modules:
	@mkdir -p public/build/
	$(YARN) install
.PHONY: node_modules

wait-for-db:
	@echo " Waiting for database..."
	@for i in {1..5}; do $(EXEC_DB) mysql -u$(DB_USER) -p$(DB_PWD) -e "SELECT 1;" > /dev/null 2>&1 && sleep 1 || echo " Unavailable..." ; done;
.PHONY: wait-for-db

db: dev-db migrations ## Reset the development database
.PHONY: db

dev-db: wait-for-db
	-$(SYMFONY_CONSOLE) doctrine:database:drop --if-exists --force
	-$(SYMFONY_CONSOLE) doctrine:database:create --if-not-exists
.PHONY: dev-db

test-db: wait-for-db ## Create a database for testing
	@echo "doctrine:database:drop"
	@APP_ENV=test $(SYMFONY_CONSOLE) --env=test doctrine:database:drop --if-exists --force
	@echo "doctrine:database:create"
	@APP_ENV=test $(SYMFONY_CONSOLE) --env=test doctrine:database:create
	@echo "doctrine:schema:create"
	@APP_ENV=test $(SYMFONY_CONSOLE) --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
.PHONY: test-db

migrations:
	$(SYMFONY_CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration
.PHONY: migrations

assets: node_modules ## Run Webpack to compile assets
	@mkdir -p public/build/
	$(YARN) run dev
.PHONY: assets

##
## Project-specific commands
## ─────────────────────────
##

elastic: ## Populate ElasticSearch database
	$(SYMFONY_CONSOLE) fos:elastica:reset-templates
	$(SYMFONY_CONSOLE) fos:elastica:reset
.PHONY: elastic
