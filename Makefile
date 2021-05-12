.DEFAULT_GOAL := help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

clean:
	@docker-compose down --remove-orphans

start: ## Initialize project & start all containers
	mkdir -p .docker/database
	@docker-compose up -d --build --force-recreate
	@docker-compose exec app composer install -o -n
	@docker-compose exec app bin/console doctrine:database:create --if-not-exists
	@docker-compose exec app bin/console doctrine:schema:update --force
	@docker-compose exec app bin/console fos:elastica:populate

dev: clean ## Starts dev stack
	sudo chmod -R 777 .docker/database
	@docker-compose up -d --build --force-recreate
	@docker-compose exec app bin/console fos:elastica:populate
