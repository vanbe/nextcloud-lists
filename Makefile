.PHONY: setup dev build test test-php test-js lint docker-up docker-down docker-logs docker-enable package

DC = docker compose -f docker-compose.dev.yml
OCC = $(DC) exec --user www-data nextcloud php occ

setup:
	composer install
	npm ci

dev:
	npm run dev

build:
	npm run build
	composer install --no-dev -o

test: test-php test-js

test-php:
	./vendor/bin/phpunit --config tests/php/phpunit.xml

test-js:
	npm run test

lint:
	./vendor/bin/psalm
	npm run lint

docker-up:
	$(DC) up -d

docker-down:
	$(DC) down

docker-logs:
	$(DC) logs -f

docker-install:
	# NC33+: the entrypoint auto-installs via env vars; just fix perms and enable the app
	$(DC) exec nextcloud chown www-data:www-data /var/www/html/custom_apps
	$(OCC) config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
	$(OCC) app:enable lists

docker-enable:
	$(OCC) config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
	$(OCC) app:enable lists

package:
	mkdir -p build/artifacts
	composer install --no-dev -o
	npm run build
	tar --exclude='.git' \
	    --exclude='node_modules' \
	    --exclude='vendor' \
	    --exclude='tests' \
	    --exclude='src' \
	    --exclude='build' \
	    --exclude='docker-compose.dev.yml' \
	    -czf build/artifacts/lists.tar.gz \
	    -C .. lists
