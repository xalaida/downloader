# Build Docker containers
install: build composer.install

# Run the testing server
server:
	docker compose up -d server

# Start Docker containers
up:
	docker compose up -d

# Stop Docker containers
down:
	docker compose down

# Restart Docker containers
restart:
	docker compose restart

# Build Docker containers
build:
	docker compose build

# Show status of Docker containers
ps:
	docker compose ps

# Install Composer dependencies
composer.install:
	docker compose run --rm app composer install

# Update Composer dependencies
composer.update:
	docker compose run --rm app composer update

# Downgrade Composer dependencies to lowest versions
composer.lowest:
	docker compose run --rm app composer update --prefer-lowest --prefer-stable

# Show outdated dependencies
composer.outdated:
	docker compose run --rm app composer outdated

# Uninstall Composer dependencies
composer.uninstall:
	sudo rm -rf vendor
	sudo rm composer.lock

# Dump Composer autoload
autoload:
	docker compose run --rm app composer dump-autoload

# Run the testsuite
test:
	docker compose run --rm app vendor/bin/phpunit

# Generate a coverage report as HTML
coverage.html:
	docker compose run --rm app vendor/bin/phpunit --coverage-html tests/report

# Generate a coverage report as plain text
coverage.text:
	docker compose run --rm app vendor/bin/phpunit --coverage-text

# Alias to generate a coverage report as plain text
coverage: coverage.text

# Fix the code style
style:
	docker compose run --rm app vendor/bin/php-cs-fixer fix

# Check the code style
style.dry:
	docker compose run --rm app vendor/bin/php-cs-fixer fix --dry-run --diff-format udiff

# Remove installation files
uninstall: down composer.uninstall
