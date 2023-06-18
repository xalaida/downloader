# Build docker containers
install: build composer.install

# Run the testing server
server:
	docker compose up -d server

# Start docker containers
up:
	docker compose up -d

# Stop docker containers
down:
	docker compose down

# Restart docker containers
restart:
	docker compose restart

# Build docker containers
build:
	docker compose build

# Show status of docker containers
ps:
	docker compose ps

# Install app dependencies
composer.install:
	docker compose run --rm app composer install

# Update app dependencies
composer.update:
	docker compose run --rm app composer update

# Downgrade Composer dependencies to lowest versions
composer.lowest:
	docker compose run --rm app composer update --prefer-lowest --prefer-stable

# Show outdated dependencies
composer.outdated:
	docker compose run --rm app composer outdated

# Uninstall composer dependencies
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock

# Dump composer autoload
autoload:
	docker compose run --rm app composer dump-autoload

# Run the testsuite
test:
	docker compose run --rm app vendor/bin/phpunit

# Generate a coverage report as html
coverage.html:
	docker compose run --rm app vendor/bin/phpunit --coverage-html tests/report

# Generate a coverage report as text
coverage.text:
	docker compose run --rm app vendor/bin/phpunit --coverage-text

# Coverage text alias
coverage: coverage.text

# Fix the code style
style:
	docker compose run --rm app vendor/bin/php-cs-fixer fix

# Check the code style
style.dry:
	docker compose run --rm app vendor/bin/php-cs-fixer fix --dry-run --diff-format udiff

# Remove installation files
uninstall: down composer.uninstall
