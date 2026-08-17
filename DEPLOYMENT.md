# Guide de déploiement — Render

## Problème résolu (2026-08-17)

**Erreur 500 "Server Error" lors de la connexion** sur https://immo-api-master-8.onrender.com/

### Cause
Le Dockerfile ne lançait pas `php artisan migrate` avant de démarrer le serveur web. Résultat : aucune table n'existait dans la base de données, et les requêtes SQL causaient une erreur PDO non capturée → HTTP 500.

### Solution appliquée
Ajout de `php artisan migrate --force` au Dockerfile :
```dockerfile
CMD php artisan migrate --force && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

---

## Checklist de déploiement

Pour tout redéploiement ou création d'une nouvelle instance Render, vérifier :

### 1. Variables d'environnement Render (Dashboard Render)

Définir **obligatoirement** dans Render (Settings → Environment):

| Clé | Valeur | Exemple |
|-----|--------|---------|
| `APP_KEY` | Généré via `php artisan key:generate` | `base64:Z5fxQeQG0kQYp9uErx2ayIboyexzwXguytORBQBR1u4=` |
| `APP_URL` | URL publique Render | `https://immo-api-master-8.onrender.com` |
| `APP_ENV` | `production` | `production` |
| `APP_DEBUG` | `false` | `false` |
| `DB_CONNECTION` | `mysql` | `mysql` |
| `DB_HOST` | Serveur MySQL (externe) | `gestion-immo.render.com` |
| `DB_PORT` | `3306` | `3306` |
| `DB_DATABASE` | Nom de la base | `gestion_immo` |
| `DB_USERNAME` | Utilisateur MySQL | `root` |
| `DB_PASSWORD` | Mot de passe MySQL | *(à définir)* |
| `GOOGLE_CLIENT_ID` | OAuth Android ID | *(depuis Firebase)* |
| `LOG_LEVEL` | `debug` ou `error` | `debug` |

**⚠️ Ne jamais commiter `.env` dans Git** — Render utilise les variables d'environnement du dashboard.

### 2. Database

- Base MySQL doit exister avec `utf8mb4_unicode_ci` collation
- User MySQL doit avoir tous les droits sur la base
- **Migrations s'exécutent automatiquement** au démarrage du conteneur (voir Dockerfile)
- Si les migrations échouent, le serveur ne démarrera pas

### 3. Dockerfile

Vérifier que le `CMD` contient **dans cet ordre** :
1. `php artisan migrate --force` — crée les tables
2. `php artisan storage:link --force` — crée le lien public/storage
3. `php artisan serve --host=0.0.0.0 --port=$PORT` — démarre Laravel

### 4. Tests post-déploiement

```bash
# 1. Health check
curl https://immo-api-master-8.onrender.com/up

# 2. Test login (doit retourner 422 avec message d'identifiants invalides, pas 500)
curl -X POST https://immo-api-master-8.onrender.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"test@example.com","password":"test"}'

# Réponse attendue :
# {"success":false,"message":"The given data was invalid.","errors":{"login":["Identifiants invalides."]}}

# 3. Vérifier les logs
# Dashboard Render → Logs (voir les migrations executées)
```

---

## Troubleshooting

### Erreur 500 persistante
1. **Migrations n'ont pas roulé** : vérifier les logs Render (Dashboard → Logs)
2. **Base de données inaccessible** : vérifier DB_HOST, DB_USERNAME, DB_PASSWORD
3. **APP_KEY invalide** : générer une nouvelle clé `php artisan key:generate` locally et la copier en env

### Fichiers/images en 404
Vérifier que `php artisan storage:link --force` s'exécute dans le Dockerfile

### Logs vides
Vérifier `LOG_CHANNEL` et `LOG_LEVEL` en env

---

## Notes

- Sans volume persistant Render (version gratuite), le filesystem est éphémère → toute image uploadée sera perdue au redéploiement
- Les sessions et cache utilisent le driver `database` (conforme)
- Queue utilise `sync` car pas de worker permanent sur mono-service
