


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

## 5bis. Comportements implémentés (itérations 2026-05-29)

Optimisations principalement pensées pour Android / petits écrans.

**Densité & marges mobiles.** Sous 768px, les paddings horizontaux sont réduits (`.lists-view`, `.item-list`, `h2`) pour récupérer de la largeur. Le `padding-top: 44px` du titre reste (toggle `NcAppNavigation`).

**Quantités — badge + picker (composant `QuantityPicker.vue`).** Les `+/−` inline sont remplacés par un **badge `×N` cliquable** (sur chaque item ET dans la barre d'ajout `ItemInput`). Au clic :
- **Desktop (≥768px)** : popover ancré au badge, input `type=number` auto-focus.
- **Mobile (<768px)** : **bottom sheet** pleine largeur (pas de débordement, à côté du clavier numérique natif). Chips rapides `1 2 3 5 10`, input `inputmode=numeric`, `−/+/OK`, **swipe-down pour fermer**, backdrop tap = fermer. Pas d'auto-focus (n'ouvre pas le clavier d'emblée).

**Bouton d'ajout.** Texte « Ajouter » sur desktop, **`+` carré** sur mobile (`aria-label` conservé).

**Compteurs.** Nombre d'éléments **actifs · cochés** sous le titre ; `(N)` dans le séparateur « Cochés » et dans chaque en-tête de catégorie.

**Suggestions de l'`ItemInput`.** Déclenchées dès **1 caractère** (seuil aligné PHP + JS). Chaque suggestion montre la quantité actuelle (`×N`) + un hint adaptatif (`déjà dans la liste`, `sera décoché`, `… · qté → X`). Si l'utilisateur a saisi une quantité avant de choisir une suggestion, elle est **transférée** à l'item (et un item coché est **réactivé**, pas dupliqué).

**Suppression d'item.** Confirmation via `ConfirmModal` (titre de l'item). La croix `✕` reste **visible (grisée) sur tactile** (`@media (hover: none)`).

**Catégories.** Sur les items actifs, le badge affiche **l'icône** de la catégorie si elle en a une (sinon le nom). Ordre des contrôles à droite identique cochés/non-cochés : crayon → catégorie → delete. Ajout de catégorie : uniquement via la modale ⚙ (le `+` direct a été retiré).

> Glitch connu sans impact prod : en **Responsive Design Mode Firefox**, le contenu peut « disparaître » au survol souris (artefact RDM lié à `display: contents`). Inexistant sur tactile réel (pas de hover).

---
