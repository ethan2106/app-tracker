# App Tracker - Suivi des mises à jour d'applications Windows

Un outil web simple et efficace pour suivre les mises à jour de vos applications Windows favorites. Détecte automatiquement les nouvelles versions et vous notifie quand des mises à jour sont disponibles.

## ✨ Fonctionnalités

- **Suivi automatique** : Vérification automatique des dernières versions depuis diverses sources
- **Sources multiples** : Support de Mozilla Firefox, Node.js, Python, VLC, Git, 7-Zip et repos GitHub personnalisés
- **Interface moderne** : UI responsive avec design moderne et intuitif
- **Notifications visuelles** : Indicateurs colorés pour les apps à jour / nécessitant une mise à jour
- **Gestion d'erreurs** : Détection et affichage des problèmes de vérification
- **Liens de téléchargement** : Accès direct aux pages de téléchargement officielles
- **Cache intelligent** : Évite les appels API trop fréquents (10 minutes)
- **Authentification GitHub** : Support du token GitHub pour éviter les limites de taux

## 🚀 Installation

### Prérequis

- **PHP 8.3+** avec extensions PDO et cURL
- **MySQL/MariaDB**
- **Serveur web** (Apache/Nginx) ou PHP built-in server
- **Composer** (optionnel, pour les dépendances futures)

### Installation rapide avec Laragon (recommandé)

1. **Cloner ou télécharger** le projet dans `C:\laragon\www\app-tracker\`

2. **Configuration de la base de données** :
   ```sql
   -- Créer la base de données
   CREATE DATABASE app_tracker;

   -- Exécuter le script db.sql
   SOURCE C:\laragon\www\app-tracker\db.sql;
   ```

3. **Configuration PHP** :
   - Ouvrir `config.php` et ajuster les paramètres de connexion MySQL
   - Créer un fichier `.env` pour le token GitHub (optionnel mais recommandé) :
     ```
     GITHUB_TOKEN=votre_token_github_ici
     ```

4. **Démarrer Laragon** et accéder à `http://localhost/app-tracker/`

### Installation alternative

1. **Télécharger** les fichiers dans votre répertoire web
2. **Configurer** la base de données MySQL
3. **Ajuster** `config.php` avec vos paramètres
4. **Démarrer** votre serveur web

## 📖 Utilisation

### Ajouter une application

1. Cliquer sur **"Ajouter une App"**
2. Remplir :
   - **Nom** : Nom de l'application
   - **Version actuelle** : Version installée
   - **Source de mise à jour** : Choisir parmi :
     - `mozilla` (Firefox)
     - `nodejs` (Node.js)
     - `python` (Python)
     - `vlc` (VLC Media Player)
     - `git` (Git for Windows)
     - `7zip` (7-Zip)
     - `owner/repo` (pour les repos GitHub personnalisés)

### Vérifier les mises à jour

- **Vérification individuelle** : Bouton "Vérifier MAJ" pour chaque app
- **Vérification globale** : Bouton "Vérifier toutes les MAJ"
- **Cache** : Les vérifications sont mises en cache pendant 10 minutes

### Interpréter les résultats

- 🟢 **À jour** : Version actuelle = dernière version
- 🔴 **MAJ disponible** : Nouvelle version détectée
- ⚠️ **Erreur** : Problème de vérification (cliquer pour voir les détails)
- 📥 **Télécharger** : Lien vers la page de téléchargement officielle

## 🔧 Configuration avancée

### Token GitHub (recommandé)

Pour éviter les limites de taux des API GitHub :

1. Aller sur [GitHub Settings > Developer settings > Personal access tokens](https://github.com/settings/tokens)
2. Créer un token avec scope `public_repo`
3. L'ajouter dans le fichier `.env` :
   ```
   GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
   ```

### Sources personnalisées

Pour ajouter une nouvelle source d'API :

1. Ajouter le cas dans `getLatestVersion()` dans `functions.php`
2. Ajouter l'URL dans `getDownloadUrl()` si applicable
3. Tester avec une app existante

### Cache et performance

- **Délai de cache** : 10 minutes (modifiable dans `checkForUpdates()`)
- **Timeout HTTP** : 10 secondes par défaut
- **Rate limiting** : Automatique via cache et token GitHub

## 🛠️ Développement

### Structure du projet

```
app-tracker/
├── index.php              # Point d'entrée principal
├── config.php             # Configuration base de données
├── functions.php          # Logique métier et API
├── db.sql                 # Schéma base de données
├── .env                   # Variables d'environnement (token GitHub)
├── .vscode/               # Configuration VS Code
│   ├── settings.json      # Config PHP/Intelephense
│   ├── launch.json        # Debug configurations
│   └── tasks.json         # Tâches VS Code
├── templates/             # Templates HTML
│   ├── list_apps.php      # Page principale
│   ├── add_app.php        # Formulaire ajout
│   ├── edit_app.php       # Formulaire modification
│   └── logs.php           # Page d'erreurs
└── assets/                # Ressources statiques
    ├── css/
    │   └── style.css      # Styles modernes
    └── js/                # JavaScript (futur)
```

### API et sources supportées

| Source | URL API | Description |
|--------|---------|-------------|
| Mozilla | `product-details.mozilla.org` | Firefox versions |
| Node.js | `nodejs.org/dist/index.json` | Releases Node.js |
| Python | `python.org/api/v2/downloads/` | Releases Python |
| VLC | Scraping `videolan.org` | Version VLC |
| Git | `api.github.com/repos/git-for-windows/git` | Git for Windows |
| 7-Zip | Scraping `7-zip.org` | Version 7-Zip |
| GitHub | `api.github.com/repos/owner/repo` | Repos personnalisés |

### Base de données

```sql
CREATE TABLE apps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(50) NOT NULL,
    update_source VARCHAR(100),
    latest_version VARCHAR(50),
    latest_version_norm VARCHAR(50),
    update_available TINYINT DEFAULT 0,
    last_checked DATETIME,
    last_error TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🤝 Contribution

1. **Fork** le projet
2. **Créer** une branche pour votre fonctionnalité
3. **Commiter** vos changements
4. **Push** vers la branche
5. **Créer** une Pull Request

### Idées d'améliorations

- [ ] Notifications par email
- [ ] Intégration avec des gestionnaires de paquets (Chocolatey, Winget)
- [ ] API REST pour intégrations tierces
- [ ] Thèmes sombre/clair
- [ ] Export/Import de configurations
- [ ] Historique des versions
- [ ] Tests automatisés

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🆘 Support

- **Issues** : [GitHub Issues](https://github.com/votre-username/app-tracker/issues)
- **Documentation** : Ce README et les commentaires dans le code
- **Communauté** : Discussions GitHub

---

**Développé avec ❤️ en PHP procédural moderne**