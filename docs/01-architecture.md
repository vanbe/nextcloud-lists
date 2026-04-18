
### Endpoints REST (OCS)

Préfixe : `/ocs/v2.php/apps/lists/api/v1`

```
GET    /lists                         → listes accessibles à l'utilisateur courant (owned + shared)
POST   /lists                         → crée une liste
GET    /lists/{id}                    → détail d'une liste + items
PUT    /lists/{id}                    → met à jour (name, description, icon)
DELETE /lists/{id}                    → supprime (owner only)

GET    /lists/{id}/items              → items (déjà inclus dans GET /lists/{id}, mais utile pour refresh)
POST   /lists/{id}/items              → crée un item
PUT    /lists/{id}/items/{itemId}     → toggle checked, edit title/description, reorder (position)
DELETE /lists/{id}/items/{itemId}     → supprime un item

GET    /lists/{id}/items/suggest?q=X  → autocomplete : renvoie items de la liste dont title LIKE 'X%' (max 5)

GET    /lists/{id}/shares             → partages de la liste
POST   /lists/{id}/shares             → ajoute un partage {shareType, shareWith, permissions}
PUT    /lists/{id}/shares/{shareId}   → modifie permissions
DELETE /lists/{id}/shares/{shareId}   → retire un partage

GET    /users/search?q=X              → proxy vers IUserManager::search (limité, pour l'autocomplete partage)
GET    /groups/search?q=X             → idem pour groupes
```

Toutes les réponses en JSON, code HTTP standard, erreurs métiers via `DataResponse` avec status 4xx.

---


## 3. Arborescence du projet

```
lists/
├── appinfo/
│   ├── info.xml                 # manifeste
│   ├── routes.php               # déclare toutes les routes
│   └── database.xml             # OBSOLÈTE depuis NC21, utiliser Migrations
├── lib/
│   ├── AppInfo/
│   │   └── Application.php      # bootstrap, enregistrement services & events
│   ├── Controller/
│   │   ├── ListController.php
│   │   ├── ItemController.php
│   │   ├── ShareController.php
│   │   └── PageController.php   # sert la SPA (index.php)
│   ├── Db/
│   │   ├── ListEntity.php
│   │   ├── ListMapper.php
│   │   ├── ItemEntity.php
│   │   ├── ItemMapper.php
│   │   ├── ShareEntity.php
│   │   └── ShareMapper.php
│   ├── Service/
│   │   ├── ListService.php
│   │   ├── ItemService.php
│   │   ├── ShareService.php
│   │   └── PermissionService.php
│   ├── Migration/
│   │   └── Version0001Date20260418000000.php   # création des 3 tables
│   └── Exception/
│       ├── NotFoundException.php
│       └── ForbiddenException.php
├── src/                         # sources Vue — compilées vers js/
│   ├── main.js
│   ├── App.vue
│   ├── router.js
│   ├── store/
│   │   └── index.js             # Pinia (préféré à Vuex en 2026)
│   ├── components/
│   │   ├── Navigation.vue       # sidebar : liste des listes
│   │   ├── ListView.vue         # vue d'une liste + items
│   │   ├── ListItem.vue         # une ligne (checkbox, title, edit)
│   │   ├── ItemInput.vue        # input avec autocomplete
│   │   ├── ListSettings.vue     # modale rename/icon/description
│   │   ├── ShareModal.vue       # modale de partage
│   │   └── IconPicker.vue       # sélecteur d'icône MDI
│   ├── services/
│   │   ├── api.js               # wrapper @nextcloud/axios
│   │   └── endpoints.js
│   └── utils/
│       └── sort.js              # tri items (non-cochés en haut, cochés en bas par checked_at desc)
├── css/
│   └── app.scss
├── img/
│   └── app.svg                  # icône de l'app (obligatoire, monochrome)
├── tests/
│   ├── php/
│   │   ├── Unit/                # PHPUnit sur services/mappers
│   │   └── Integration/         # contre vraie DB
│   └── js/
│       └── unit/                # Vitest sur composants et store
├── templates/
│   └── index.php                # shell HTML qui charge la SPA
├── composer.json
├── package.json
├── webpack.config.js
├── psalm.xml
├── phpunit.xml
├── Makefile                     # raccourcis build/test/release
├── krankerl.toml                # packaging (voir §7)
└── README.md
```
