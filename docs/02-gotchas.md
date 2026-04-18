
---

## 4. Points d'attention critiques (là où ça pique habituellement)

**4.1 — Migrations, pas de `database.xml`.** Depuis NC21 les schémas se déclarent via des classes de migration PHP (`OCP\Migration\SimpleMigrationStep`). Elles s'exécutent à l'installation/mise à jour de l'app. Nomme-les `VersionXXXXDateYYYYMMDDHHMMSS.php`.

**4.2 — DI, pas de `new` sauvage.** Les controllers et services reçoivent leurs dépendances via le constructeur. Le container résout automatiquement les types `OCP\*`. Pour des services custom, enregistre-les dans `Application::register()` via `$context->registerService(...)` uniquement si tu as besoin de logique custom ; sinon l'autowiring suffit.

**4.3 — `OCSController` vs `Controller`.** Utilise `OCSController` pour l'API REST (renvoie le bon wrapper OCS attendu par `@nextcloud/axios`). Utilise `Controller` classique pour la PageController qui sert le HTML.

**4.4 — CSRF.** `@nextcloud/axios` envoie automatiquement le token. Ne désactive **jamais** `@NoCSRFRequired` sauf sur un endpoint public explicite (tu n'en as pas ici).

**4.5 — Annotations de routes.** Chaque méthode de controller doit être annotée :
- `@NoAdminRequired` → accessible aux users normaux
- `@NoCSRFRequired` → à éviter
- `#[PublicPage]`, `#[NoAdminRequired]` sont les nouveaux attributs PHP 8, préfère-les aux annotations docblock si tu cibles NC30+.

**4.6 — Partage : subtilité des groupes.** Quand tu listes les listes accessibles à un user, fais une requête unique avec un `WHERE owner = :uid OR EXISTS (SELECT 1 FROM lists_shares s WHERE s.list_id = lists.id AND ((s.share_type = 0 AND s.share_with = :uid) OR (s.share_type = 1 AND s.share_with IN (:groups))))`. Récupère les groupes du user via `IGroupManager::getUserGroupIds()`.

**4.7 — L'autocomplete d'items.** Le déclenchement côté front : debounce 150ms, minimum 2 caractères, requête `GET /lists/{id}/items/suggest?q=...`. Le backend fait un `LIKE :q||'%'` **case-insensitive** (`LOWER(title) LIKE LOWER(:q) || '%'`). Limite à 5 résultats, triés par `checked ASC, checked_at DESC` (on préfère suggérer un item non coché, sinon le plus récemment coché).

**Comportement précis quand l'utilisateur clique une suggestion** (tu l'as demandé mais c'est ambigu dans l'énoncé, je fige la sémantique) :
- Si l'item suggéré est **coché** → on le décoche, on vide l'input, on scroll l'item dans le viewport, et on lui donne le focus (curseur dans son champ title en mode édition).
- Si l'item suggéré est **non coché** → idem sans le décochage. C'est juste un raccourci « cet item existe déjà, va le voir ».
- Dans les deux cas l'input est vidé et reste actif pour la saisie suivante.

**4.8 — Tri dynamique des items.** Les items cochés vont en bas, barrés. À l'intérieur de chaque groupe (cochés / non-cochés), l'ordre dépend du filtre actif (manuel via `position`, alphabétique, date de création, date de check). Implémente ça **côté front** (après réception) pour éviter des allers-retours réseau sur chaque toggle. La `position` n'est persistée que quand le user fait un drag & drop manuel.

**4.9 — Drag & drop.** `vuedraggable` (wrapper de SortableJS) marche bien avec NC. À la fin d'un drag, envoie un seul `PUT` par item déplacé avec la nouvelle position (ou mieux : un endpoint bulk `PUT /lists/{id}/items/reorder` qui prend `[{id, position}, ...]`).

**4.10 — Realtime (hors scope v1).** Pas de WebSocket natif dans Nextcloud (sauf via Notify Push qui est pénible). Pour v1, fais du polling toutes les 30s quand la vue est active, et invalide le cache au blur/focus de la fenêtre. Note-le dans le README comme limitation connue.

**4.11 — i18n.** Utilise `t('lists', 'Mon texte')` côté PHP et `t('lists', 'Mon texte')` (global Vue injecté par Nextcloud) côté JS. Génère les fichiers de traduction via `make l10n` (template fourni par la communauté Nextcloud). Pour v1 : FR + EN.

**4.12 — Sécurité XSS.** Titres et descriptions d'items sont du texte brut, jamais de HTML. Vue 3 échappe par défaut, **n'utilise jamais `v-html`** sur ces champs.

**4.15 — Commandes OCC : `register_command.php`, pas `console.php`.** En NC30, le fichier de déclaration des commandes OCC d'une app est `appinfo/register_command.php` (chargé par `lib/private/Console/Application.php`). La variable `$application` (Symfony Console App) et `$serverContainer` (IServerContainer) y sont injectées automatiquement. Exemple minimal : `$application->add($serverContainer->get(\OCA\Lists\Command\Foo::class));`. `IRegistrationContext::registerCommand()` n'existe pas en NC30.

**4.14 — `BOOLEAN NOT NULL` interdit dans les migrations NC.** Nextcloud refuse les colonnes `BOOLEAN NOT NULL` (validation cross-DB). Utilise `Types::SMALLINT` avec `default => 0` / `1` à la place. Dans l'Entity, caste avec `$this->addType('checked', 'integer')` et expose via un getter `bool` : `return (bool) $this->checked`.

**4.13 — Permissions `custom_apps` au premier boot Docker.** Le bind mount `./:/var/www/html/custom_apps/lists` force Docker à créer `/var/www/html/custom_apps` appartenant à `root:root`. Nextcloud ne peut alors pas y écrire et boucle sur "Retrying install...". Fix après `docker compose up` :
```bash
docker compose -f docker-compose.dev.yml exec nextcloud chown www-data:www-data /var/www/html/custom_apps
```
En NC33+, `maintenance:install` n'existe plus — l'entrypoint Docker installe NC automatiquement via les variables d'environnement. Ce fix (chown) est à rejouer uniquement après un `docker compose down -v`.

---