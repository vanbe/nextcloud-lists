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
Jalon en cours : **7 — Autocomplete d'items**
Dernier commit : `feat(jalon-6): partage via menu ⋮ + modale native`

### Pièges découverts (à ne pas réintroduire)
- `NcAppContent` ne rend pas son slot par défaut dans @vue/compat MODE:2 → remplacé par `<main id="app-content">` natif.
- `#lists-root` est dans `#content` (flex container NC) mais pas enfant direct → `display: contents` dans main.js après montage pour que `#app-navigation` et `#app-content` deviennent des flex items directs de NC.
- Le bouton toggle NcAppNavigation chevauche le titre → `padding-top: 44px` sur le h2.

### Jalons terminés
- ✅ **1 — Squelette + enable** : `info.xml`, `Application.php`, `PageController`, template `index.php`, icône, Docker, Makefile.
- ✅ **2 — DB + entités + mappers** : migration 3 tables (`lists`, `lists_items`, `lists_shares`), `ListEntity`/`ListMapper`, exceptions, `occ lists:debug:seed`, tests PHPUnit.
- ✅ **3 — API Lists CRUD** : `ListService`, `ListController` (OCSController), routes OCS, validé par cURL (GET/POST/PUT/DELETE + 404/403).
- ✅ **4 — SPA frontend minimale** : `package.json`, `webpack.config.js`, `src/main.js`, `App.vue` (NcAppNavigation + main#app-content), store Pinia (`lists.js`), service API (`api.js`). Build webpack OK, app Vue montée dans NC.
- ✅ **5 — Items CRUD + toggle** : `ItemEntity`, `ItemMapper`, `ItemService`, `ItemController`, routes OCS imbriquées, `itemsApi`, store `items.js`, `ItemList.vue` (ajout/toggle/suppression, tri cochés/non-cochés). Piège layout NC résolu.
- ✅ **6 — Partage** : `ShareEntity`/`ShareMapper` (sentinelle -1 obligatoire sur int), `PermissionService` (IGroupManager + IUserManager), `ShareService`/`ShareController`, `ListMapper.findAllForUser` (LEFT JOIN + GROUP BY — pas EXISTS), menu ⋮ sidebar + `ShareModal.vue`.