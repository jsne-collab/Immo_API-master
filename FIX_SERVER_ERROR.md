# Fix Résumé - Erreur Server 500 lors de la connexion

## Problème
L'application mobile affichait "Server Error" lors de la tentative de connexion sur https://immo-api-master-8.onrender.com/

## Root Cause Identified
**Le Dockerfile ne lançait pas les migrations Laravel avant le serveur.** 
- Résultat : aucune table n'était créée sur le serveur Render
- Les requêtes SQL (ex: `SELECT * FROM users WHERE email = ?`) échouaient avec une erreur PDO
- L'exception PDO n'était pas capturée → HTTP 500

## Corrections appliquées

### 1. ✅ Dockerfile - Ajout de la migration
**Fichier:** `Dockerfile`

```dockerfile
# Avant :
CMD php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT

# Après :
CMD php artisan migrate --force && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

### 2. ✅ Exception handling générique
**Fichier:** `bootstrap/app.php`

Ajout d'un renderer générique pour capturer les exceptions inattendues en API et retourner une réponse JSON cohérente au lieu d'une erreur HTML 500:

```php
$exceptions->render(function (Throwable $e, $request) {
    if ($request->is('api/*')) {
        Log::error('Unexpected API error', [
            'exception' => $e,
            'url' => $request->url(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => config('app.debug') 
                ? $e->getMessage() 
                : 'An unexpected error occurred.',
            'errors' => null,
        ], 500);
    }
});
```

### 3. ✅ Documentation de déploiement
**Fichier:** `DEPLOYMENT.md` (créé)

Guide complet pour le déploiement sur Render avec:
- Checklist des variables d'environnement
- Procédure de vérification post-déploiement
- Troubleshooting courant

## Comment tester

### Localement
```bash
# 1. Assurez-vous que les migrations ont roulé
php artisan migrate

# 2. Testez l'endpoint de connexion (doit retourner 422 avec message, pas 500)
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"test@example.com","password":"test"}'

# Réponse attendue :
# {"success":false,"message":"The given data was invalid.","errors":{"login":["Identifiants invalides."]}}
```

### Sur Render (après redéploiement)
```bash
# Health check
curl https://immo-api-master-8.onrender.com/up

# Test login
curl -X POST https://immo-api-master-8.onrender.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"test@example.com","password":"test"}'

# Vérifier les logs dans le dashboard Render → Logs
# Vous devriez voir : "php artisan migrate" s'exécuter au démarrage
```

## Points d'attention

1. **Variables d'environnement Render** : Assurez-vous que le dashboard Render a les bonnes variables (voir DEPLOYMENT.md)
2. **Migration force** : L'option `--force` permet aux migrations de s'exécuter en production
3. **Logs** : Les erreurs inattendues sont maintenant loggées avec contexte complet

## Fichiers modifiés
- `Dockerfile`
- `bootstrap/app.php`
- `DEPLOYMENT.md` (créé)
