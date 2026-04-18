
## 6. Setup de dev (Ubuntu WSL + Docker)

Un seul chemin, le plus simple :

**6.1 — Prérequis WSL** (une fois) :
```bash
sudo apt update && sudo apt install -y php8.2-cli php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-intl php8.2-sqlite3 composer nodejs npm make git
sudo npm install -g n && sudo n lts    # pour avoir un Node récent (≥20)
```

**6.2 — Container Nextcloud de dev** (`docker-compose.dev.yml` à créer à la racine du repo) :

```yaml
services:
  nextcloud:
    image: nextcloud:30-apache
    ports:
      - "8080:80"
    environment:
      NEXTCLOUD_ADMIN_USER: admin
      NEXTCLOUD_ADMIN_PASSWORD: admin
      NEXTCLOUD_TRUSTED_DOMAINS: localhost
      SQLITE_DATABASE: nextcloud
    volumes:
      - nc_data:/var/www/html
      - ./:/var/www/html/custom_apps/lists    # ← ton code monté live
    restart: unless-stopped

volumes:
  nc_data:
```

`docker compose -f docker-compose.dev.yml up -d`, puis dans le container :
```bash
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ app:enable lists
```

**6.3 — Boucle de dev** :
- Backend PHP : modifier un fichier dans `lib/` → rafraîchir la page (pas de compil).
- Frontend Vue : `npm run dev` dans le repo → webpack watch, les assets se recompilent dans `js/`, rafraîchir la page.

**6.4 — `Makefile` cibles minimales :**
```
make setup       # composer install && npm ci
make dev         # npm run dev
make build       # npm run build && composer install --no-dev -o
make test        # make test-php && make test-js
make test-php    # ./vendor/bin/phpunit --config tests/php/phpunit.xml
make test-js     # npm run test
make lint        # psalm + eslint
make docker-up   # docker compose -f docker-compose.dev.yml up -d
make docker-down # docker compose -f docker-compose.dev.yml down
make docker-logs # docker compose -f docker-compose.dev.yml logs -f
make package     # produit build/artifacts/lists.tar.gz prêt à distribuer
```
