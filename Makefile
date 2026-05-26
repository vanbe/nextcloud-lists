.PHONY: setup dev build test test-php test-js lint docker-up docker-down docker-logs docker-enable package

DC  = docker compose -f docker-compose.dev.yml
OCC = $(DC) exec --user www-data nextcloud php occ

setup:
	composer install
	npm ci

dev:
	npm run dev

build:
	npm run build

test: test-php test-js

test-php:
	$(DC) exec --user www-data nextcloud bash -c \
	  "cd /var/www/html/custom_apps/lists && ./vendor/bin/phpunit -c phpunit.xml --testdox"

test-js:
	npm run test

lint:
	npm run lint

docker-up:
	$(DC) up -d

docker-down:
	$(DC) down

docker-logs:
	$(DC) logs -f

docker-install:
	$(DC) exec nextcloud chown www-data:www-data /var/www/html/custom_apps
	$(OCC) config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
	$(OCC) app:enable lists

docker-enable:
	$(OCC) config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
	$(OCC) app:enable lists

package:
	@echo "→ Building JS assets…"
	npm ci
	npm run build
	@echo "→ Creating archive…"
	mkdir -p build/artifacts
	$(eval TMPDIR := $(shell mktemp -d))
	rsync -a \
	  --exclude='.git' \
	  --exclude='node_modules' \
	  --exclude='src' \
	  --exclude='tests' \
	  --exclude='build' \
	  --exclude='docs' \
	  --exclude='vendor' \
	  --exclude='webpack.config.js' \
	  --exclude='package.json' \
	  --exclude='package-lock.json' \
	  --exclude='composer.json' \
	  --exclude='composer.lock' \
	  --exclude='phpunit.xml' \
	  --exclude='vitest.config.js' \
	  --exclude='docker-compose.dev.yml' \
	  --exclude='Makefile' \
	  --exclude='CLAUDE.md' \
	  --exclude='krankerl.toml' \
	  --exclude='.nextcloudignore' \
	  --exclude='.claude' \
  --exclude='local' \
	  --exclude='js/*.map' \
	  ./ $(TMPDIR)/lists/
	tar -czf build/artifacts/lists.tar.gz -C $(TMPDIR) lists
	rm -rf $(TMPDIR)
	@echo "✓ Archive: build/artifacts/lists.tar.gz"
	@tar -tzf build/artifacts/lists.tar.gz | head -30
