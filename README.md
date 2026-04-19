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

## Installation (production)

1. Extract the archive into your Nextcloud custom apps directory:
   ```bash
   tar -xzf lists.tar.gz -C /var/www/nextcloud/custom_apps/
   ```
2. Enable the app:
   ```bash
   sudo -u www-data php /var/www/nextcloud/occ app:enable lists
   ```
3. Migrations run automatically on first enable.

> **Nextcloud AIO**: place the extracted `lists/` folder in the volume mounted at `custom_apps/`, then enable via the AIO interface or `occ`.

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

**App doesn't appear after enable**
```bash
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ config:system:set apps_paths 1 path --value=/var/www/html/custom_apps
docker compose -f docker-compose.dev.yml exec --user www-data nextcloud \
  php occ app:enable lists
```

**Internal server error on load**
Check the NC log:
```bash
docker compose -f docker-compose.dev.yml exec nextcloud \
  tail -f /var/www/html/data/nextcloud.log
```

**Migrations not applied after a schema change**
Disable and re-enable the app to trigger migration:
```bash
occ app:disable lists && occ app:enable lists
```

**Frontend not updating**
Run `npm run build` (or `npm run dev` for watch mode) then hard-refresh (`Ctrl+Shift+R`).

---

## License

AGPL-3.0-or-later — see [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.html).
