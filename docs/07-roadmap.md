
## 9. Ordre de travail recommandé (évite les culs-de-sac)

Fais-le dans cet ordre, ne saute pas d'étape, commit à chaque jalon.

1. **Squelette + enable.** `info.xml`, `Application.php` vide, `PageController` qui rend un `index.php` avec « Hello ». Enable l'app dans le container. Vérifie qu'elle apparaît dans la top bar de Nextcloud. Tant que ce n'est pas le cas, rien ne sert de coder la suite.
2. **DB + entités + mappers.** Migration, `ListEntity`/`ListMapper`, tests unitaires du mapper. Crée une liste via un script CLI (`occ lists:debug:seed`) pour valider.
3. **API Lists (CRUD basique, sans partage).** Controller + Service, test cURL au token OCS (`curl -u admin:admin -H "OCS-APIRequest: true" ...`).
4. **SPA frontend minimale.** `main.js`, `App.vue`, appel API, affichage liste. Pas de style, juste que ça marche.
5. **Items (CRUD + toggle).** Controller, service, mapper, UI. Tri cochés/non-cochés côté front. Animations.
6. **Partage.** Model + endpoints + PermissionService. Teste avec deux users dans le container (`occ user:add bob`).
7. **Autocomplete d'items.** Endpoint suggest + `ItemInput.vue` avec comportement précis §4.7.
8. **Modale de partage + recherche user/groupe.** `NcSelectUsers` branché sur `/users/search`.
9. **Polish UX.** Responsive, loaders, toasts d'erreur (`@nextcloud/dialogs`), drag & drop, filtres/tri, icônes.
10. **Tests.** PHPUnit sur PermissionService en premier (c'est le plus sensible), puis Vitest sur ItemInput.
11. **i18n FR/EN.**
12. **Packaging.** krankerl, test d'install « propre » sur un second container vierge.
13. **README + docs déploiement.**

---

## 10. Livrables finaux attendus

- Repo complet conforme à l'arbo §3.
- `docker-compose.dev.yml` fonctionnel (un `make docker-up` + `make build` + enable et ça tourne sur `http://localhost:8080`).
- Archive `lists.tar.gz` produite par `make package`, installable sur une instance NC vierge.
- `README.md` avec : 3 commandes pour lancer en dev, 3 commandes pour installer en prod, 1 section troubleshooting.
- Couverture de tests ≥ 60% sur `lib/Service/` (c'est là que vit la logique métier).

---

## 11. Ce que tu ne fais PAS en v1

Note-le dans un `ROADMAP.md`, ça évite les dérives de scope :
- Sous-listes / catégories d'items.
- Dates d'échéance, rappels, notifications push.
- Pièces jointes sur un item.
- Historique / activités (Nextcloud Activity app integration).
- Export CSV/JSON.
- Realtime collaboratif.
- Partage par lien public.

Garde ça pour v1.1+.