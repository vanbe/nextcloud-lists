# Projet : App Nextcloud "Lists"

Application Nextcloud de TODO / listes de courses, avec partage user/groupe.

## Stack
- Backend : PHP 8.2, framework Nextcloud (NC 30+)
- Frontend : Vue 3 + @nextcloud/vue + Pinia
- DB : abstraction QBMapper (SQLite en dev, MariaDB/PG en prod)
- Dev env : Docker (nextcloud:33-apache) + WSL Ubuntu

## Documentation du projet
AVANT de coder une nouvelle partie, lis le doc correspondant :
- Architecture générale : `docs/01-architecture.md`
- **Pièges à éviter (à relire souvent) : `docs/02-gotchas.md`**
- Specs UI : `docs/03-ui-ux.md`
- Setup dev : `docs/04-dev-setup.md`
- Packaging : `docs/05-packaging.md`
- Tests : `docs/06-testing.md`
- **Ordre de travail imposé : `docs/07-roadmap.md`** — on suit les jalons 1 à 13 dans l'ordre.

## Règles de travail
1. Respecte STRICTEMENT l'ordre des jalons du roadmap. Pas de saut.
2. À chaque jalon terminé : commit git avec message `feat(jalon-N): description`.
3. Avant d'écrire du code PHP Nextcloud, consulte `docs/02-gotchas.md`.
4. Aucun SQL brut non portable. Toujours QBMapper / QueryBuilder.
5. Aucun `v-html` dans les composants Vue.
6. Si tu bloques : lis les logs du container avant de tenter un workaround.

## État actuel
Jalon en cours : **4 — SPA frontend minimale**
Dernier commit : `feat(jalon-3): API Lists CRUD (ListController + ListService)`

### Jalons terminés
- ✅ **1 — Squelette + enable** : `info.xml`, `Application.php`, `PageController`, template `index.php`, icône, Docker, Makefile.
- ✅ **2 — DB + entités + mappers** : migration 3 tables (`lists`, `lists_items`, `lists_shares`), `ListEntity`/`ListMapper`, exceptions, `occ lists:debug:seed`, tests PHPUnit.
- ✅ **3 — API Lists CRUD** : `ListService`, `ListController` (OCSController), routes OCS, validé par cURL (GET/POST/PUT/DELETE + 404/403).