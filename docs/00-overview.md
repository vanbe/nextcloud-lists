
## 1. Contexte et philosophie Nextcloud

Avant de coder, internalise ces points — ils conditionnent 80 % des choix d'architecture :

**Une app Nextcloud = un dossier dans `apps/` (ou `custom_apps/`) de l'instance.** Elle suit une structure imposée par le framework. Pas de liberté là-dessus : respecte la convention ou rien ne marchera.

**Stack imposée :**
- Backend : PHP 8.1+ (PHP 8.2 recommandé pour Nextcloud 29/30/31), framework maison basé sur Symfony (DI container, routing, ORM léger « QBMapper »).
- Frontend : Vue 3 + `@nextcloud/vue` (librairie de composants officielle, qui réplique l'UI Nextcloud), Webpack via `@nextcloud/webpack-vue-config`.
- Base de données : abstraction via `IDBConnection` / `QBMapper` (compatible MySQL, MariaDB, PostgreSQL, SQLite). **Ne jamais écrire de SQL brut non portable.**
- API : REST via `OCSController` (renvoie JSON/XML, gère CSRF et auth automatiquement).

**Ne réinvente pas ce qui existe :**
- Partage utilisateurs/groupes → utilise `IShareManager` **ou** une table de partage maison calquée sur le pattern de l'app Deck. Pour ton cas (partage de liste), une table maison `lists_shares` est plus simple et plus lisible que de se brancher sur le ShareManager global qui est conçu pour les fichiers.
- Recherche d'utilisateurs/groupes → `OCP\IUserManager`, `OCP\IGroupManager`, et côté front le composant `NcSelectUsers` de `@nextcloud/vue`.
- Icônes → Material Design Icons via `vue-material-design-icons` (déjà utilisé partout dans Nextcloud).

**Version cible :** Nextcloud 30 minimum (dernière stable à date — vérifie au moment du `composer install`). Déclare `min-version=30` et `max-version=32` dans `appinfo/info.xml`.

---

## 2. Architecture fonctionnelle

### Entités

```
List (lists)
 ├── id (int, PK)
 ├── owner_uid (string, FK users) 
 ├── name (string, 255)
 ├── description (text, nullable)
 ├── icon (string, 64) — nom d'une icône MDI, ex: "cart", "check-all"
 ├── created_at (timestamp)
 └── updated_at (timestamp)

ListItem (lists_items)
 ├── id (int, PK)
 ├── list_id (int, FK lists, ON DELETE CASCADE)
 ├── title (string, 255)
 ├── description (text, nullable)
 ├── checked (bool, default false)
 ├── position (int) — ordre manuel
 ├── checked_at (timestamp, nullable)
 ├── created_at (timestamp)
 └── updated_at (timestamp)

ListShare (lists_shares)
 ├── id (int, PK)
 ├── list_id (int, FK lists, ON DELETE CASCADE)
 ├── share_type (int) — 0=user, 1=group (on suit la convention Nextcloud)
 ├── share_with (string, 64) — uid ou gid
 ├── permissions (int, default 31) — bitmask: 1=read, 2=update, 4=create, 8=delete, 16=share
 └── created_at (timestamp)

UNIQUE(list_id, share_type, share_with)
INDEX sur lists_items(list_id, checked, position)
INDEX sur lists_shares(share_with, share_type)
```

**Règle de permission à implémenter dans un service `PermissionService` :**
- Propriétaire = tous les droits.
- Sinon, droits = OR bitwise sur tous les shares qui matchent (user direct + groupes dont il est membre).
- Expose une méthode `canView($listId, $userId)`, `canEdit(...)`, `canDelete(...)`, `canShare(...)`.
- **Chaque endpoint** commence par un check de permission. Pas d'exception.

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