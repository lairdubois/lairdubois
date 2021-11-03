.DEFAULT_GOAL := help

help:
	@grep -E '(^[a-zA-Z_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

clean:
	@docker-compose down --remove-orphans

clean.all: ## Kill containers and remove volumes
	@docker-compose down --remove-orphans --volumes

install: ## Initialize project & start all containers
	@cp docker-compose.override.yml.dist docker-compose.override.yml						# Copy default custom docker composer configuration
	@cp .env .env.local																		# Copy default custom symfony environment vars
	@docker-compose up -d --build --force-recreate
	@docker-compose exec symfony composer install -o -n										# Install symfony components
	@docker-compose exec symfony bin/console doctrine:database:create --if-not-exists		# Create database
	@docker-compose exec symfony bin/console doctrine:schema:update --force					# Update database schema
	@docker-compose exec symfony bin/console fos:elastica:reset-templates					# Reset elasticsearche templates
	@docker-compose exec symfony bin/console fos:elastica:reset								# Reset elasticsearch indices
	@docker-compose exec symfony yarn encore dev											# Build assets

start: ## Starts dev stack
	@docker-compose start

stop: ## Stops dev stack
	@docker-compose stop
