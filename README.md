# Toubilib - Guide de Test avec Thunder Client

**Auteurs** : Guillaume Hess, Mirac Demirci

## Prérequis

1. **Extension VS Code** : Installer Thunder Client dans VS Code
2. **Services Docker** : Les services doivent être démarrés
   ```bash
   cd toubilib
   docker compose up --build -d
   ```
3. **URL de base** : `http://localhost:6080`

## Tests d'Authentification JWT avec (ThunderClient)

### Test 0 : Inscription Nouveau Patient
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signup`
- **Headers** : `Content-Type: application/json`
- **Body (JSON)** :
```json
{
  "email": "nouveau.patient@test.com",
  "password": "motdepasse123",
  "password_confirmation": "motdepasse123"
}
```
- **Résultat attendu** : `201 Created`
```json
{
  "success": true,
  "message": "Account created successfully",
  "auth": {
    "profile": {
      "id": "abc123...",
      "email": "nouveau.patient@test.com",
      "role": 1,
      "role_label": "patient"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```
- **Action** : Le patient est automatiquement connecté, tokens JWT fournis

### Test 0.1 : Inscription Échouée (Email Déjà Existant)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signup`
- **Body (JSON)** :
```json
{
  "email": "patient1@test.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
- **Résultat attendu** : `400 Bad Request`
```json
{
  "error": "Email already exists"
}
```

### Test 0.2 : Inscription Échouée (Mot de passe trop court)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signup`
- **Body (JSON)** :
```json
{
  "email": "test@test.com",
  "password": "1234",
  "password_confirmation": "1234"
}
```
- **Résultat attendu** : `400 Bad Request`
```json
{
  "error": "Password must be at least 8 characters"
}
```

### Test 0.3 : Inscription Échouée (Mots de passe différents)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signup`
- **Body (JSON)** :
```json
{
  "email": "test@test.com",
  "password": "password123",
  "password_confirmation": "password456"
}
```
- **Résultat attendu** : `400 Bad Request`
```json
{
  "error": "Passwords do not match"
}
```

### Test 1 : Connexion Réussie (Patient)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signin`
- **Headers** : `Content-Type: application/json`
- **Body (JSON)** :
```json
{
  "email": "patient1@test.com",
  "password": "password123"
}
```
- **Résultat attendu** : `200 OK`
```json
{
  "profile": {
    "id": "a0000001-0000-4000-8000-000000000001",
    "email": "patient1@test.com",
    "role": 1,
    "role_label": "patient"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```
- **Action** : Copier le `access_token` pour les tests suivants

### Test 2 : Connexion Réussie (Praticien)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signin`
- **Body (JSON)** :
```json
{
  "email": "praticien1@test.com",
  "password": "password123"
}
```
- **Résultat attendu** : `200 OK` avec `role: 10`

### Test 3 : Connexion Réussie (Admin)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signin`
- **Body (JSON)** :
```json
{
  "email": "admin@test.com",
  "password": "password123"
}
```
- **Résultat attendu** : `200 OK` avec `role: 100`

### Test 4 : Connexion Échouée (Mauvais mot de passe)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/signin`
- **Body (JSON)** :
```json
{
  "email": "patient1@test.com",
  "password": "wrongpassword"
}
```
- **Résultat attendu** : `401 Unauthorized`
```json
{
  "error": "Invalid credentials"
}
```

### Test 4.1 : Rafraîchissement du Token (Access Token Expiré)
- **Note** : L'access token expire après **15 heure**. Le refresh token expire après **30 jours**.
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/refresh`
- **Headers** : `Content-Type: application/json`
- **Body (JSON)** :
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```
- **Résultat attendu** : `200 OK`
```json
{
  "profile": {
    "id": "a0000001-0000-4000-8000-000000000001",
    "email": "patient1@test.com",
    "role": 1,
    "role_label": "patient"
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc... (nouveau token)",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc... (nouveau token)"
}
```
- **Action** : Utiliser le nouveau `access_token` pour les requêtes suivantes

### Test 4.2 : Rafraîchissement Échoué (Refresh Token Invalide)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/auth/refresh`
- **Body (JSON)** :
```json
{
  "refresh_token": "invalid_token"
}
```
- **Résultat attendu** : `401 Unauthorized`
```json
{
  "error": "Invalid refresh token"
}
```

## Tests d'Authentification Middleware

### Test 5 : Accès sans Token
- **Méthode** : `GET`
- **URL** : `http://localhost:6080/praticiens/4305f5e9-be5a-4ccf-8792-7e07d7017363/agenda`
- **Headers** : aucun
- **Résultat attendu** : `401 Unauthorized`
```json
{
  "error": "Missing or invalid access token"
}
```

### Test 6 : Accès avec Token Invalide
- **Méthode** : `GET`
- **URL** : `http://localhost:6080/praticiens/4305f5e9-be5a-4ccf-8792-7e07d7017363/agenda`
- **Headers** : `Authorization: Bearer invalid_token_here`
- **Résultat attendu** : `401 Unauthorized`

### Test 7 : Accès avec Token Valide
- **Méthode** : `GET`
- **URL** : `http://localhost:6080/praticiens/4305f5e9-be5a-4ccf-8792-7e07d7017363/agenda`
- **Headers** : `Authorization: Bearer {access_token du Test 1}`
- **Résultat attendu** : `200 OK` ou `404 Not Found` (selon les données)

## Tests d'Autorisation (Rôles)

### Test 8 : Patient Crée un RDV (Autorisé)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/rdvs`
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer {token patient du Test 1}`
- **Body (JSON)** :
```json
{
  "praticien_id": "4305f5e9-be5a-4ccf-8792-7e07d7017363",
  "specialite_label": "cardiologue",
  "date": "2025-12-15",
  "heure": "14:00",
  "motif": "scanner"
}
```
- **Résultat attendu** : `201 Created`

### Test 9 : Praticien Crée un RDV (Refusé)
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/rdvs`
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer {token praticien du Test 2}`
- **Body (JSON)** : (même body que Test 8)
- **Résultat attendu** : `403 Forbidden`
```json
{
  "error": "Access denied"
}
```

### Test 9.1 : Création RDV - Créneau Occupé (Conflit)
- **Note** : La vérification des indisponibilités vérifie automatiquement les créneaux déjà pris par le praticien
- **Méthode** : `POST`
- **URL** : `http://localhost:6080/rdvs`
- **Headers** :
  - `Content-Type: application/json`
  - `Authorization: Bearer {token patient du Test 1}`
- **Body (JSON)** :
```json
{
  "praticien_id": "4305f5e9-be5a-4ccf-8792-7e07d7017363",
  "patient_id": "a0000001-0000-4000-8000-000000000001",
  "date_heure_debut": "2025-12-22T10:00:00",
  "duree": 30,
  "motif_visite": "scanner"
}
```
- **Étape 1** : Créer un premier RDV avec les données ci-dessus → devrait retourner `201 Created`
- **Étape 2** : Tenter de créer un deuxième RDV au même créneau ou qui chevauche
- **Résultat attendu (étape 2)** : `400 Bad Request`
```json
{
  "success": false,
  "code": "praticien_unavailable",
  "message": "Praticien indisponible pour ce créneau"
}
```

### Test 10 : Praticien Accède à son Agenda (Autorisé)
- **Méthode** : `GET`
- **URL** : `http://localhost:6080/praticiens/{id}/agenda`
- **Headers** : `Authorization: Bearer {token praticien}`
- **Résultat attendu** : `200 OK` (si autorisé pour cet ID)



## Comptes de Test

| Email | Mot de passe | Rôle | ID |
|-------|--------------|------|-----|
| patient1@test.com | password123 | Patient (1) | a0000001-0000-4000-8000-000000000001 |
| patient2@test.com | password123 | Patient (1) | a0000002-0000-4000-8000-000000000002 |
| praticien1@test.com | password123 | Praticien (10) | 4305f5e9-be5a-4ccf-8792-7e07d7017363 |
| praticien2@test.com | password123 | Praticien (10) | b0000002-0000-4000-8000-000000000002 |
| admin@test.com | password123 | Admin (100) | c0000001-0000-4000-8000-000000000001 |

