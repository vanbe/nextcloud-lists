
---

## 8. Stratégie de tests

**8.1 — PHP (PHPUnit)**
- Unit : tester `PermissionService` (cas owner / user share / group share / aucun accès), `ListService::create`, `ItemService::reorder`.
- Integration : mapper contre SQLite en mémoire, vérifier CRUD + contraintes (cascade delete, unique share).
- Config : `phpunit.xml` avec un bootstrap qui charge `lib/base.php` de Nextcloud (variable d'env `NEXTCLOUD_PATH`).
- **Commande locale** : `docker compose exec --user www-data nextcloud bash -c "cd /var/www/html/custom_apps/lists && ./vendor/bin/phpunit"`.

**8.2 — JS (Vitest)**
- Composants clés avec `@vue/test-utils` : `ItemInput` (autocomplete, debounce, comportement suggestion), `ListItem` (toggle, edit), store Pinia (actions optimistes, rollback en cas d'erreur API).
- Mock de l'axios via `vi.mock('@nextcloud/axios')`.

**8.3 — E2E (optionnel v1)** : Playwright contre le container de dev. Un seul scénario happy path : login → créer liste → ajouter item → cocher → partager → vérifier visibilité pour user2. Utile mais peut attendre v1.1 si tu veux livrer vite.

**8.4 — CI** : un `.github/workflows/ci.yml` qui fait `make lint && make test && make package`. Pousse l'artifact en cas de tag `v*`.

---
