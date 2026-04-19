# Lists — Nextcloud App

TODO lists and shopping lists for Nextcloud, with user/group sharing, categories, and quantities.

## Features

- Create and manage multiple lists
- Add items with optional quantities
- Organize items by category
- Check/uncheck items; clear checked items in one click
- Share lists with users or groups (read or read+write)
- Autocomplete when adding items already in the list
- Full French translation (auto-loaded when session language is `fr`)

## Requirements

- Nextcloud 30 – 34
- PHP 8.2+

---

## Installation — Nextcloud AIO (Docker All-In-One)

AIO manages Nextcloud inside a container named `nextcloud-aio-nextcloud`. Custom apps must be copied into the container's `custom_apps/` directory, which lives inside a named Docker volume and persists across AIO updates.

**1. Build the archive** (on your dev machine):
```bash
make package
# → build/artifacts/lists.tar.gz
```

**2. Copy the archive to your server**, then run:
```bash
# Extract into the AIO Nextcloud container
docker exec nextcloud-aio-nextcloud mkdir -p /var/www/html/custom_apps
docker cp lists.tar.gz nextcloud-aio-nextcloud:/tmp/lists.tar.gz
docker exec nextcloud-aio-nextcloud \
  tar -xzf /tmp/lists.tar.gz -C /var/www/html/custom_apps/
docker exec nextcloud-aio-nextcloud \
  chown -R www-data:www-data /var/www/html/custom_apps/lists

# Enable the app (migrations run automatically)
docker exec --user www-data nextcloud-aio-nextcloud \
  php occ app:enable lists
```

**3. Verify** — navigate to your Nextcloud instance; "Lists" should appear in the app menu.

> The `custom_apps/` directory is part of the Nextcloud data volume, so the app **survives AIO updates and container restarts**. You only need to re-run `occ app:enable lists` if AIO explicitly resets app state.

### Updating the app on AIO

```bash
# On your dev machine
make package

# On the server — replace the app files
docker cp lists.tar.gz nextcloud-aio-nextcloud:/tmp/lists.tar.gz
docker exec nextcloud-aio-nextcloud \
  tar -xzf /tmp/lists.tar.gz -C /var/www/html/custom_apps/
docker exec nextcloud-aio-nextcloud \
  chown -R www-data:www-data /var/www/html/custom_apps/lists

# Apply any new migrations
docker exec --user www-data nextcloud-aio-nextcloud \
  php occ app:disable lists
docker exec --user www-data nextcloud-aio-nextcloud \
  php occ app:enable lists
```

---

## Installation — standard (non-AIO)

1. Extract the archive into your Nextcloud custom apps directory:
   ```bash
   tar -xzf lists.tar.gz -C /var/www/nextcloud/custom_apps/
   ```
2. Enable the app:
   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:enable lists
   ```
3. Migrations run automatically on first enable.

---

## Development setup

**Prerequisites** (once, on WSL/Ubuntu):
```bash
sudo apt install -y nodejs npm make git
```

**Start**:
```bash
git clone https://github.com/charlesvanbeneden/nextcloud-lists.git
cd nextcloud-lists
make docker-up          # starts Nextcloud on http://localhost:8080
make docker-enable      # registers the app path and enables the app
npm ci && npm run dev   # webpack watch — frontend rebuilt on save
```

Backend (PHP) changes take effect on page refresh with no rebuild step.

**All Makefile targets**:

| Command | Description |
|---|---|
| `make setup` | `composer install && npm ci` |
| `make dev` | webpack watch |
| `make build` | production JS build |
| `make test` | PHP + JS tests |
| `make test-php` | PHPUnit in container |
| `make test-js` | Vitest |
| `make lint` | ESLint |
| `make docker-up` | start container |
| `make docker-down` | stop container |
| `make docker-logs` | follow container logs |
| `make package` | produce `build/artifacts/lists.tar.gz` |

---

## Packaging

```bash
make package
# → build/artifacts/lists.tar.gz
```

The archive contains only runtime files (`appinfo/`, `lib/`, `js/`, `l10n/`, `img/`, `templates/`). No `node_modules`, `vendor`, `src`, or dev config files are included.

---

## Troubleshooting

**App doesn't appear in the menu (AIO)**
```bash
docker exec --user www-data nextcloud-aio-nextcloud php occ app:enable lists
```
If that fails with "app not found", check that `custom_apps/lists/appinfo/info.xml` exists inside the container:
```bash
docker exec nextcloud-aio-nextcloud ls /var/www/html/custom_apps/lists/appinfo/
```

**Internal server error (AIO)**
```bash
docker exec nextcloud-aio-nextcloud tail -50 /var/www/html/data/nextcloud.log
```

**App doesn't appear after enable (dev container)**
```bash
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ app:enable lists
```

**Migrations not applied after a schema change**
Disable and re-enable the app to trigger migration:
```bash
# AIO
docker exec --user www-data nextcloud-aio-nextcloud php occ app:disable lists
docker exec --user www-data nextcloud-aio-nextcloud php occ app:enable lists
```

**Frontend not updating**
Run `npm run build` then hard-refresh (`Ctrl+Shift+R`).

---

## License

AGPL-3.0-or-later — see [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.html).
