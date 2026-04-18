
---

## 7. Packaging & déploiement

**7.1 — Archive distribuable.** Une app Nextcloud se distribue en `tar.gz` contenant un dossier `lists/` à la racine avec **uniquement** :
- `appinfo/`, `lib/`, `templates/`, `img/`, `css/`, `js/` (compilé), `l10n/`, `vendor/` (si dépendances Composer runtime)
- **Exclure** : `src/`, `node_modules/`, `tests/`, `.git/`, `*.md` (sauf README), fichiers de config dev (`webpack.config.js`, `package.json`, `composer.json` optionnel selon politique).

La liste d'exclusion va dans un `.nextcloudignore` à la racine. Utilise `krankerl` (l'outil officiel de packaging d'apps NC) — installable via cargo ou en binaire. `krankerl.toml` :
```toml
[package]
before_cmds = [
    "composer install --no-dev -o",
    "npm ci",
    "npm run build",
]
```
Puis `krankerl package` produit l'archive signée dans `build/artifact/`.

**7.2 — Script `make package` fallback** (si krankerl pose problème) : un script bash qui copie dans un dossier temporaire, supprime ce qui est dans `.nextcloudignore`, et tar.gz le tout. Je préfère krankerl, garde le bash comme plan B.

**7.3 — Déploiement sur prod :**
Deux voies.
- **Voie manuelle (celle que tu vas utiliser en premier)** :
  1. Sur le serveur, en tant qu'utilisateur web (souvent `www-data`) : extraire l'archive dans `/var/www/nextcloud/custom_apps/lists/` (ou `apps/`).
  2. `sudo -u www-data php /var/www/nextcloud/occ app:enable lists`.
  3. Les migrations s'exécutent automatiquement.
- **Voie app store Nextcloud** : publie l'archive sur `apps.nextcloud.com`, l'admin clique « installer » dans l'UI. Hors scope v1, mais prévois le `info.xml` compatible dès maintenant (champs `<author>`, `<category>`, `<website>`, `<bugs>`, `<repository>` renseignés).

**7.4 — Documentation `README.md`** : prérequis, installation (3 étapes max), configuration (aucune), troubleshooting. Garde-la courte, renvoie vers `docs/` pour le détail.
