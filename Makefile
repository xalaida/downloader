# Start app containers
up:
	docker-compose up -d

# Build app containers
build:
	docker-compose build

# Stop app containers
down:
	docker-compose down

# Run the testsuite
test:
	docker-compose run --rm app vendor/bin/phpunit

# Fix the code style
fix:
	docker-compose run --rm app vendor/bin/php-cs-fixer fix

# Install app dependencies
composer.install:
	docker-compose run --rm app composer install

# Update app dependencies
composer.update:
	docker-compose run --rm app composer update

# Show outdated dependencies
composer.outdated:
	docker-compose run --rm app composer outdated

# Dump composer autoload
autoload:
	docker-compose run --rm app composer dump-autoload

# Generate a coverage report as html
coverage.html:
	docker-compose run --rm app vendor/bin/phpunit --coverage-html tests/report

# Generate a coverage report as text
coverage.text:
	docker-compose run --rm app vendor/bin/phpunit --coverage-text

# Coverage text alias
coverage: coverage.text

# Build the server container
server.build:
	docker-compose build server

# Install composer dependencies in the server container
server.install:
	docker-compose run --rm server composer install

# Run the server container
server.start:
	docker-compose up server
