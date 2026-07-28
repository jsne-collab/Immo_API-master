# API — Gestion Locative

Base URL : `{APP_URL}/api/v1` (voir `.env` — utiliser l'IP LAN, pas `localhost`, pour un appareil physique).

## Authentification

Toutes les routes sauf `auth/register`, `auth/login`, `auth/forgot-password`, `auth/reset-password`, `auth/verify-email`, `auth/google` et `GET health` exigent un header :

```
Authorization: Bearer {token}
```

Le token est un token Sanctum obtenu via `POST auth/login` ou `POST auth/register`, avec une expiration de `SANCTUM_TOKEN_EXPIRATION_MINUTES` minutes (7 jours par défaut). `POST auth/refresh` révoque le token courant et en émet un nouveau ; `POST auth/logout` le révoque sans en émettre.

## Format de réponse

Toutes les réponses suivent la même enveloppe :

```json
{ "success": true, "data": { ... }, "message": "..." }
```

```json
{ "success": false, "message": "...", "errors": { "champ": ["message de validation"] } }
```

Les listes paginées renvoient `data` sous la forme :

```json
{ "items": [ ... ], "pagination": { "current_page": 1, "last_page": 3, "per_page": 15, "total": 42 } }
```

## Codes d'erreur

| Code | Signification |
|---|---|
| 401 | Non authentifié (token absent/invalide/expiré) |
| 403 | Authentifié mais non autorisé pour cette ressource (policy) |
| 404 | Ressource introuvable |
| 422 | Validation échouée (Form Request) ou règle métier violée (`ValidationException`) |
| 429 | Trop de requêtes (routes soumises à throttling) |

## Rôles

`owner` (propriétaire), `tenant` (locataire), `admin` (bypass toutes les policies via `before()`). La plupart des ressources sont scopées automatiquement par rôle (un propriétaire ne voit que ses biens/baux/paiements, un locataire que les siens).

---

## Authentification (10) — `/auth`

Règle : un compte créé via Google Sign-In (`google_id` renseigné, `password` null) reste bloqué (403 sur toutes les routes métier hors `/auth/*`) tant que `profile_completed` est `false` — voir `PUT /auth/complete-profile`.

| Méthode | Endpoint | Rôle | Throttle | Description |
|---|---|---|---|---|
| POST | `/register` | public | 5/min | Inscription (`name`, `email`, `phone`, `password`, `password_confirmation`, `role: owner\|tenant`, `terms_accepted`, `privacy_accepted`) |
| POST | `/login` | public | 5/min | `login` (email ou téléphone) + `password` → `{ user, token }` |
| POST | `/google` | public | 5/min | `id_token` (Google) + `device_type` → vérifie le jeton auprès de Google, crée/lie/retrouve le compte, `{ user, token }` |
| POST | `/logout` | auth | — | Révoque le token courant |
| POST | `/refresh` | auth | — | Révoque et réémet un token |
| PUT | `/complete-profile` | auth | — | `role`, `phone`, `terms_accepted`, `privacy_accepted` — obligatoire après une première connexion Google |
| POST | `/forgot-password` | public | 5/min | Envoie un lien/code de réinitialisation (rejette les comptes Google-only avec un message dédié) |
| POST | `/reset-password` | public | 5/min | Réinitialise avec le token reçu |
| POST | `/verify-email` | public | 5/min | Vérifie l'email via code OTP (6 chiffres, expire 10 min) |
| GET | `/me` | auth | — | Profil de l'utilisateur connecté |

## Utilisateurs & Profils (6) — `/users`

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | admin (libre) / owner (recherche uniquement) | Liste — un owner doit fournir `search` (nom/email/téléphone), résultats limités aux tenants |
| GET | `/{id}` | soi-même ou admin | Détail utilisateur + profil |
| PUT | `/{id}` | soi-même ou admin | Mise à jour profil |
| DELETE | `/{id}` | soi-même ou admin | Suppression du compte (mot de passe requis) |
| POST | `/{id}/avatar` | soi-même ou admin | Upload avatar (multipart, `avatar`) |
| PUT | `/{id}/password` | soi-même ou admin | Changement de mot de passe (révoque les autres sessions) |

## Biens immobiliers (9) — `/properties`

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner | Biens du propriétaire connecté |
| POST | `/` | owner | Création |
| GET | `/search` | auth | Recherche parmi les biens `available` (`city`, `type`, `price_min`, `price_max`, `q`) |
| GET | `/available` | auth | Tous les biens `available` |
| GET | `/{id}` | owner du bien, tout utilisateur si `available`, ou locataire avec bail actif dessus | Détail |
| PUT | `/{id}` | owner du bien | Modification |
| DELETE | `/{id}` | owner du bien | Suppression |
| POST | `/{id}/images` | owner du bien | Upload image (multipart, `image`, `is_primary`) |
| DELETE | `/{id}/images/{imageId}` | owner du bien | Suppression d'une image |

## Unités locatives (5) — `/properties/{id}/units`, `/units`

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/properties/{id}/units` | owner du bien | Liste des unités |
| POST | `/properties/{id}/units` | owner du bien | Création |
| GET | `/units/{id}` | owner du bien parent | Détail |
| PUT | `/units/{id}` | owner du bien parent | Modification |
| DELETE | `/units/{id}` | owner du bien parent | Suppression |

## Contrats de location (7) — `/leases`

Règles A.6.1/A.6.2/A.6.6 : un bien/unité n'a qu'un bail `active` à la fois ; le bien passe `rented` à l'activation et `available` à la résiliation/expiration ; un job planifié (`leases:expire`, quotidien) expire les baux dépassés.

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner ou tenant concerné | Liste (scope automatique) |
| POST | `/` | owner | Création — génère le contrat PDF, journalisé dans `activity_logs` |
| GET | `/{id}` | owner ou tenant du bail | Détail |
| PUT | `/{id}` | owner du bail | Modification |
| POST | `/{id}/terminate` | owner du bail | Résiliation, journalisée |
| POST | `/{id}/renew` | owner du bail | Renouvellement (nouvelle `end_date`) |
| GET | `/{id}/download` | owner ou tenant du bail | URL du contrat PDF |

## Paiements (8) — `/payments`

Règle A.6.3 : uniquement sur un bail `active`. Règle A.6.4 : quittance générée en job asynchrone uniquement pour un paiement `validated` (journalisé dans `activity_logs`), notifie le locataire.

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner ou tenant concerné | Liste, filtres `lease_id`, `property_id`, `status`, `from`, `to` |
| POST | `/` | owner | Enregistrement manuel (espèces/virement déjà reçu) |
| GET | `/history` | owner ou tenant concerné | Alias de la liste |
| POST | `/initiate` | tenant | Paiement initié par le locataire (statut `pending`), throttle 10/min |
| GET | `/stats` | owner ou tenant concerné | Totaux par statut |
| GET | `/{id}` | owner ou tenant du paiement | Détail |
| PUT | `/{id}` | owner du bail | Mise à jour (ex. validation) |
| DELETE | `/{id}` | owner du bail, statut `pending` uniquement | Suppression |

## Quittances (3) — `/receipts`

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner ou tenant concerné | Liste |
| GET | `/{id}` | owner ou tenant du paiement associé | Détail |
| GET | `/{id}/download` | owner ou tenant du paiement associé | URL du PDF |

## Maintenance (7) — `/maintenance-requests`

Règle A.6.5 : un locataire ne peut créer une demande que pour un bien où il a un bail actif. Auteur (tenant) gère le contenu tant que `status = new` ; seul le propriétaire du bail change le statut.

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner ou tenant concerné | Liste |
| POST | `/` | tenant | Création (`property_id`, `title`, `description`, `priority`, `photo?`) — notifie le propriétaire |
| GET | `/{id}` | tenant auteur ou owner du bail | Détail avec fil de commentaires |
| PUT | `/{id}` | tenant auteur, `status = new` uniquement | Modification du contenu |
| DELETE | `/{id}` | tenant auteur, `status = new` uniquement | Suppression |
| POST | `/{id}/comments` | tenant auteur ou owner du bail | Ajoute un commentaire — notifie l'autre partie |
| PUT | `/{id}/status` | owner du bail | Change le statut (`new`/`in_progress`/`resolved`/`rejected`) |

## Charges / Dépenses (5) — `/expenses`

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | owner | Liste, filtres `property_id`, `category`, `from`, `to` |
| POST | `/` | owner du bien | Création |
| GET | `/{id}` | owner de la charge | Détail |
| PUT | `/{id}` | owner de la charge | Modification |
| DELETE | `/{id}` | owner de la charge | Suppression |

## Messagerie (5) — `/conversations`, `/messages`

Un message ne peut être envoyé qu'entre un propriétaire et un locataire liés par (au moins) un bail. Pas de table `conversations` dédiée : dérivées des messages, groupées par interlocuteur.

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/conversations` | auth | Liste des conversations (dernier message + non-lus) |
| GET | `/conversations/{userId}/messages` | auth | Fil complet avec cet interlocuteur — marque les messages reçus comme lus |
| POST | `/messages` | owner ou tenant lié par bail | Envoi — notifie le destinataire |
| PUT | `/messages/{id}/read` | destinataire du message | Marque comme lu |
| DELETE | `/messages/{id}` | expéditeur du message | Suppression |

## Notifications (4) — `/notifications`

Créées automatiquement lors d'événements clés : paiement validé, nouveau message, nouvelle demande de maintenance, nouveau commentaire, rappel de paiement (A.6.7, voir `payments:send-reminders`, quotidien).

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/` | auth | Liste de l'utilisateur connecté |
| PUT | `/read-all` | auth | Marque toutes ses notifications comme lues |
| PUT | `/{id}/read` | destinataire | Marque comme lue |
| DELETE | `/{id}` | destinataire | Suppression |

## Tableau de bord (4) — `/dashboard`

`owner` et `revenue` sont mis en cache 5 min (invalidé automatiquement à chaque paiement/changement de bail).

| Méthode | Endpoint | Rôle | Description |
|---|---|---|---|
| GET | `/owner` | owner | Revenus du mois (+ variation), occupation, paiements en attente, biens disponibles, paiements récents, demandes en cours |
| GET | `/tenant` | tenant | Bail actif, prochaine échéance, historique récent, demandes en cours |
| GET | `/revenue` | owner | Série des revenus validés sur 6 mois |
| GET | `/occupancy` | owner | Taux d'occupation global + détail par bien |

## Santé

`GET /health` — vérifie que l'API répond, sans authentification.

---

## Règles de gestion (A.6.x)

| # | Règle | Où |
|---|---|---|
| 1 | Un bien/unité n'a qu'un bail `active` à la fois | `LeaseService::create/renew` |
| 2 | Le bien passe `rented`/`available` selon le cycle de vie du bail | `LeaseService` |
| 3 | Un paiement n'est enregistré que sur un bail `active` | `PaymentService::assertLeaseActive` |
| 4 | Quittance générée en job asynchrone, uniquement si `validated` | `PaymentService`, `GenerateReceiptJob` |
| 5 | Demande de maintenance uniquement sur un bail actif du locataire | `MaintenanceRequestService::create` |
| 6 | Bail `active` → `expired` à échéance dépassée (planifié quotidien) | `LeaseService::expireOverdueLeases`, `leases:expire` |
| 7 | Rappel de paiement `PAYMENT_REMINDER_DAYS_BEFORE` jours avant échéance | `PaymentReminderService`, `payments:send-reminders` |
| 8 | Un utilisateur ne consulte que ce qui le concerne | Policies (voir tableaux ci-dessus) |
| 9 | Dépôt de garantie obligatoire à la création d'un bail | `StoreLeaseRequest` (`deposit_amount` requis) |
