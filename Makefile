# Start docker containers
up:
	docker-compose up -d

# Stop docker containers
down:
	docker-compose down

# Build docker containers
build:
	docker-compose build

# Show status of docker containers
ps:
	docker-compose ps

# Run the testsuite
test:
	docker-compose run --rm php7 vendor/bin/phpunit

# Fix the code style
fix:
	docker-compose run --rm php7 vendor/bin/php-cs-fixer fix

# Install app dependencies
composer.install:
	docker-compose run --rm php7 composer install

# Update app dependencies
composer.update:
	docker-compose run --rm php7 composer update

# Show outdated dependencies
composer.outdated:
	docker-compose run --rm php7 composer outdated

# Dump composer autoload
autoload:
	docker-compose run --rm php7 composer dump-autoload

# Generate a coverage report as html
coverage.html:
	docker-compose run --rm php7 vendor/bin/phpunit --coverage-html tests/report

# Generate a coverage report as text
coverage.text:
	docker-compose run --rm php7 vendor/bin/phpunit --coverage-text

# Coverage text alias
coverage: coverage.text

# Set up ownership for the current user
own:
	sudo chown -R "$(shell id -u):$(shell id -g)" .

# Run the testing server
server:
	docker-compose up -d server
