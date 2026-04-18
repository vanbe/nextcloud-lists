


## 5. Spécifications UI/UX

**Layout :** le pattern standard Nextcloud `NcAppContent` + `NcAppNavigation`.
- Sidebar gauche (`NcAppNavigation`) : liste des listes, avec icône + nom + badge count d'items non cochés. Bouton « + Nouvelle liste » en haut.
- Zone principale (`NcAppContent`) : titre de la liste sélectionnée, barre d'actions (partager, renommer, supprimer, filtre/tri), input d'ajout d'item en haut de la zone des items, puis la liste.
- Sur mobile : sidebar repliable via le toggle built-in de `NcAppNavigation` (responsive automatique).

**ListItem — un seul composant, trois états :**
1. **Lecture** : checkbox + titre (barré si coché) + bouton edit qui apparaît au hover (ou toujours visible sur touch).
2. **Édition** : titre devient un `NcTextField`, description apparaît en dessous en `NcTextArea`, boutons Save/Cancel.
3. **Coché** : opacity 0.6, text-decoration line-through, chevré vers le bas de sa section.

**Animations :** quand on coche/décoche, transition douce (FLIP animation via `vuedraggable` ou `vue-transition-group` avec `<TransitionGroup name="list">`) pour que l'item « glisse » visuellement vers sa nouvelle position. Sans ça, c'est désagréable à l'œil.

**Accessibilité :** checkbox = vrai `<input type="checkbox">`, labels associés, navigation clavier (Enter dans l'input = ajoute l'item et garde le focus pour enchaîner), Echap ferme les modales.

**Responsive :**
- Breakpoint mobile à 768px (convention NC).
- Sur mobile : les boutons d'action passent dans un `NcActions` (menu three-dots).
- L'input d'ajout reste toujours sticky en haut.

---
