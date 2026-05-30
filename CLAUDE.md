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

## Mise en production (cloud.vanbe.be)

> ⚠️ **GATE i18n — bloquant avant TOUTE mise en prod.**
> Toute string visible passe par `t('lists', '…')` / `n('lists', '…')` (simple quote uniquement).
> Avant de déployer, vérifier que **100 % des clés source ont une traduction FR** :
> 1. Extraire les clés : `grep -rhoE "[tn]\('lists', '([^']|\\')*'" src/ | sort -u`.
> 2. Comparer au contenu de `l10n/fr.json` → **0 clé manquante** exigé.
> 3. `l10n/fr.js` (format `OC.L10N.register`) et `l10n/fr.json` doivent contenir **les mêmes paires** —
>    générer `fr.js` depuis `fr.json` plutôt que d'éditer les deux à la main.
> Les fichiers `l10n/*` sont servis directement par Nextcloud → **pas de rebuild webpack** après les avoir modifiés.

**Quand rebuilder ?** Seules les modifs dans `src/` nécessitent `npm run build`. Les modifs `l10n/` et `lib/` (PHP) non — un simple rafraîchissement de page suffit.

> ⚠️ **Bump de version = `occ upgrade` obligatoire.** Dès que je modifie `<version>` dans `appinfo/info.xml`, Nextcloud détecte un écart code↔DB et affiche « Mise à jour nécessaire » (browser update souvent désactivé). Il faut lancer la migration :
> - **Dev :** `docker compose -f docker-compose.dev.yml exec --user www-data nextcloud php occ upgrade`
> - **Prod AIO :** `docker exec nextcloud-aio-nextcloud php occ upgrade`
> `occ upgrade` rejoue les migrations DB **et** désactive le mode maintenance. Ne bumper la version que quand on est prêt à migrer (ou migrer juste après).

**Cible :** `cloud.vanbe.be` = **Nextcloud AIO** (Docker) sur la VM `cloud` (10.10.10.98), pilotée via le repo `../infra` (MCP `homelab-ops`, outils `ssh_exec` / `ssh_put_file`). `occ` tourne **dans le conteneur** : `docker exec nextcloud-aio-nextcloud php occ …`.

**Procédure** (validée 2026-05-30 sur 0.3.3 → 0.3.5 cf. gotcha 4.21) :
1. **Snapshot Proxmox** de la VM `cloud` (VMID 111) avant — filet de rollback DB (le backup code seul ne suffit pas).
2. Recos : `occ app:list | grep lists` (install vs upgrade) + `occ config:system:get apps_paths` (repo custom-apps **writable** de l'AIO).
3. **Packager localement** : `cd /root/code/nextcloud-lists && tar -czf build/artifacts/lists.tar.gz` avec les exclusions du `.nextcloudignore` (src/, node_modules/, tests/, docs/, .git/, dev/, *.map, …). `scp` vers `cloud:/tmp/lists.tar.gz`.
4. **Backup de l'install actuelle HORS de `custom_apps/`** (gotcha 4.21 : un sous-dossier de custom_apps est scanné comme app candidate → casse `occ upgrade`). Cible : `data/lists-backups/lists.backup-<STAMP>/`.
   ```bash
   docker exec nextcloud-aio-nextcloud bash -c "mkdir -p /var/www/html/data/lists-backups && mv /var/www/html/custom_apps/lists /var/www/html/data/lists-backups/lists.backup-$(date +%Y%m%d-%H%M%S)"
   ```
5. **Extract + chown** :
   ```bash
   docker cp /tmp/lists.tar.gz nextcloud-aio-nextcloud:/tmp/
   docker exec nextcloud-aio-nextcloud bash -c "cd /var/www/html/custom_apps && tar -xzf /tmp/lists.tar.gz && rm /tmp/lists.tar.gz"
   docker exec nextcloud-aio-nextcloud chown -R www-data:www-data /var/www/html/custom_apps/lists
   ```
6. **`occ upgrade`** (rejoue les migrations DB + désactive maintenance) :
   ```bash
   docker exec --user www-data nextcloud-aio-nextcloud php occ upgrade
   ```
   Si maintenance reste active après un crash, `php occ maintenance:mode --off` après avoir fixé la cause.
7. **Post-checks** : `occ app:list` montre la nouvelle version, `curl -sI https://cloud.vanbe.be/index.php/apps/lists/` retourne **401** (auth requise = bon signe, pas 500/404), libellés en français au login, test rapide ajout + quantité + toggle.
8. **Cleanup** : `rm /tmp/lists.tar.gz` côté host. Garder le backup dans `data/lists-backups/` quelques jours puis purger.

## Pièges découverts (à ne pas réintroduire)
- `NcAppContent` ne rend pas son slot par défaut dans @vue/compat MODE:2 → remplacé par `<main id="app-content">` natif.
- `#lists-root` est dans `#content` (flex container NC) mais pas enfant direct → `display: contents` dans main.js après montage pour que `#app-navigation` et `#app-content` deviennent des flex items directs de NC.
- Le bouton toggle NcAppNavigation chevauche le titre → `padding-top: 44px` sur le h2.
- NC `IRequest` utilise `isset()` pour les paramètres → JSON `null` est invisible. Utiliser `0` comme sentinelle "désassigner" côté JS, traiter `0 → null` en PHP.
- NC `Entity::setter()` ignore les valeurs identiques à l'init → initialiser les champs `int` nullable à `-1` (pas `0`) pour que `setField(0)` marque le champ comme modifié.
- `findAllForUser` avec LEFT JOIN : utiliser `GROUP BY l.id` pour éviter les doublons quand plusieurs shares matchent. Un subquery EXISTS échoue avec le préfixe de table NC.
- `\OCP\Util::imagePath()` n'existe pas dans NC 33 → construire l'URL manuellement (`/apps/{appid}/img/...`). En template, `\OCP\Server::get(\OCP\IURLGenerator::class)->imagePath('lists','app.svg')` fonctionne.
- **Icône d'app (`img/app.svg`)** : convention NC = **`fill="#fff"`** (blanc) sur fond transparent, **pas** de noir ni de fond coloré. NC n'inverse pas — le header est bleu foncé donc l'icône doit déjà être blanche (vérifié : `apps/files`, `apps/logreader` core en `#fff`).
- **Favicon ≠ icône header** : l'onglet navigateur a besoin d'un fond (sinon blanc invisible). Fichier dédié `img/favicon.svg` = glyphe blanc sur carré arrondi **bleu NC `#0082c9`**, référencé via `<link rel="icon">` dans `templates/index.php`. `app.svg` (blanc/transparent) reste pour le header.
- `getCurrentUser()` de `@nextcloud/auth` pour obtenir l'UID de session — `window.OC?.currentUser` peut être vide dans le contexte Vue.
- **i18n manuel** (pas Transifex) : `l10n/fr.js` et `l10n/fr.json` maintenus à la main → garder les deux synchro (générer `fr.js` depuis `fr.json`). Voir le GATE i18n ci-dessus.
- **Seuil de l'autocomplete dupliqué** : la longueur min déclenchant les suggestions existe **côté PHP** (`ItemController::suggest`) ET **côté JS** (`ItemInput.onInput`) → toujours modifier les deux ensemble.
- **Dev Docker — bind-mount périmé** : `./:/var/www/html/custom_apps/lists` peut pointer vers un snapshot Docker Desktop vide (dossier `lists/` vide dans le conteneur). Fix : `docker compose -f docker-compose.dev.yml down && up -d` (le volume `nc_data` est préservé → install NC intacte). Après un bump d'image NC (ex. 30→33), lancer `occ upgrade`.
- **CSS dans les `.vue`** : indentation par tabulations. Dans un bloc `@media`, les sélecteurs sont indentés d'**un** tab (pas deux) → en attaquant un `Edit`, respecter le niveau réel sinon le match échoue.
