# Projet : App Nextcloud "Lists"

Application Nextcloud de TODO / listes de courses, avec partage user/groupe.

## Stack
- Backend : PHP 8.2, framework Nextcloud (NC 30+)
- Frontend : Vue 3 + @nextcloud/vue + Pinia
- DB : abstraction QBMapper (SQLite en dev, MariaDB/PG en prod)
- Dev env : Docker (nextcloud:33-apache) + WSL Ubuntu

## Documentation du projet
- Architecture générale : `docs/01-architecture.md`
- **Pièges à éviter : `docs/02-gotchas.md`**
- Specs UI : `docs/03-ui-ux.md`
- Setup dev : `docs/04-dev-setup.md`
- Packaging : `docs/05-packaging.md`
- Tests : `docs/06-testing.md`

## Règles de travail
1. Avant d'écrire du code PHP Nextcloud, consulte `docs/02-gotchas.md`.
2. Aucun SQL brut non portable. Toujours QBMapper / QueryBuilder.
3. Aucun `v-html` dans les composants Vue.
4. Si tu bloques : lis les logs du container avant de tenter un workaround.

## Pièges découverts (à ne pas réintroduire)
- `NcAppContent` ne rend pas son slot par défaut dans @vue/compat MODE:2 → remplacé par `<main id="app-content">` natif.
- `#lists-root` est dans `#content` (flex container NC) mais pas enfant direct → `display: contents` dans main.js après montage pour que `#app-navigation` et `#app-content` deviennent des flex items directs de NC.
- Le bouton toggle NcAppNavigation chevauche le titre → `padding-top: 44px` sur le h2.
- NC `IRequest` utilise `isset()` pour les paramètres → JSON `null` est invisible. Utiliser `0` comme sentinelle "désassigner" côté JS, traiter `0 → null` en PHP.
- NC `Entity::setter()` ignore les valeurs identiques à l'init → initialiser les champs `int` nullable à `-1` (pas `0`) pour que `setField(0)` marque le champ comme modifié.
- `findAllForUser` avec LEFT JOIN : utiliser `GROUP BY l.id` pour éviter les doublons quand plusieurs shares matchent. Un subquery EXISTS échoue avec le préfixe de table NC.
- `\OCP\Util::imagePath()` n'existe pas dans NC 33 → construire l'URL manuellement (`/apps/{appid}/img/...`).
- `getCurrentUser()` de `@nextcloud/auth` pour obtenir l'UID de session — `window.OC?.currentUser` peut être vide dans le contexte Vue.
