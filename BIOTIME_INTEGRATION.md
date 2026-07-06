# Intégration Biotime 8.5 - Système de Gestion des Présences

## Configuration

### Variables d'environnement

Ajoutez les variables suivantes dans votre fichier `.env`:

```env
# URL du serveur Biotime
GUZZLE_BIOTIME_URL=http://192.168.88.2:8080

# Identifiants de connexion Biotime
BIOTIME_USERNAME=Marcellin
BIOTIME_PASSWORD=marcelo@2001
```

**Important:** Après avoir modifié le fichier `.env`, exécutez la commande suivante pour vider le cache de configuration:
```bash
php artisan config:clear
```

### Alternative: Configuration directe

Si l'URL principale ne fonctionne pas, vous pouvez utiliser l'adresse IP de la pointeuse directement:
- IP: `192.168.88.4`
- Numéro de série: `AEVL223460031`

Modifiez alors `GUZZLE_BIOTIME_URL` dans le fichier `.env`:
```env
GUZZLE_BIOTIME_URL=http://192.168.88.4:8080
```

## Documentation API

- Documentation complète: http://192.168.88.2:8080/drf-docs/
- Documentation API: http://192.168.88.2:8080/docs/
- Endpoint de rapport: http://192.168.88.2:8080/docs/api-docs/att_report.html#request
- Authentification JWT: http://192.168.88.2:8080/docs/api-docs/get_auth_token.html
- Utilisation du token: http://192.168.88.2:8080/docs/api-docs/use_auth_token.html

### Endpoints utilisés

1. **Authentification:**
   - JWT Token: `POST /jwt-api-token-auth/`
   - General Token: `POST /api-token-auth/`
   - Headers: `Content-Type: application/json`
   - Body: `{"username": "username", "password": "password"}`
   - Response: `{"token": "..."}`

2. **Rapport de présence:**
   - Endpoint: `GET /att/api/transactionReport/`
   - Headers: 
     - `Content-Type: application/json`
     - `Authorization: JWT {token}`
   - Query Parameters:
     - `page` (optionnel)
     - `page_size` (optionnel)
     - `start_date` (format: YYYY-MM-DD)
     - `end_date` (format: YYYY-MM-DD)
     - `departments` (IDs séparés par des virgules)
     - `areas` (IDs séparés par des virgules)

## Utilisation

1. Accédez à la page des présences via le menu HR > Absences
2. Utilisez les filtres pour sélectionner:
   - Département(s)
   - Employé(s)
   - Période de dates
3. Cliquez sur "Rechercher" pour charger les données

## Structure des données

Le système tente automatiquement plusieurs formats de réponse de l'API Biotime:
- `data` array
- `results` array
- Array direct
- `attendance` array

Les colonnes affichées:
- Employee NO.
- First Name
- Last Name
- Department
- Position
- Date
- Weekday
- Check In
- Check Out
- Total Time

## Dépannage

Si vous rencontrez des erreurs:

1. Vérifiez que le serveur Biotime est accessible depuis votre serveur Laravel
2. Vérifiez les identifiants dans le fichier `.env`
3. Consultez les logs Laravel: `storage/logs/laravel.log`
4. Vérifiez la documentation API Biotime pour les endpoints exacts

