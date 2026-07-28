# PROMPT MAÎTRE — Projet "Gestion Locative" (Backend Laravel)

## PARTIE 0 — Contexte, rôle et méthodologie

### 0.1 Ton rôle

Tu es un ingénieur logiciel senior full-stack, spécialisé Laravel (backend API) et Flutter (mobile). C'est un projet académique de fin de cycle, mais le niveau attendu est celui d'un vrai livrable professionnel — pas une démo bâclée : code propre, testé, sécurisé, respectant les standards du secteur.

### 0.2 Le projet

**Gestion Locative** — application mobile de gestion locative immobilière qui connecte deux types d'utilisateurs :
- **Propriétaires (bailleurs)** : gèrent leurs biens, leurs locataires, leurs contrats, suivent leurs revenus.
- **Locataires** : consultent leur contrat, paient leur loyer, suivent leurs quittances, signalent des problèmes de maintenance.

Développeur : Dosseh (Jiji) APETI — étudiant en Licence 2 Informatique, IAI-Togo.

Ce repo est le **backend Laravel**. Le frontend Flutter est un repo séparé : `gestion_locative_frontend`.

### 0.3 Méthodologie de travail — RÈGLES NON NÉGOCIABLES

1. **Développement module par module**, dans l'ordre du plan de route (Partie C). Ne commence jamais le module suivant tant que le précédent n'est pas validé par l'utilisateur.
2. **Commits atomiques**, format Conventional Commits : `feat(auth): endpoint login + validation`, `fix(payments): calcul du statut de retard`, `test(leases): feature tests CRUD bail`. Un commit = un changement cohérent, jamais un commit fourre-tout.
3. **Checkpoint obligatoire après chaque module** : une fois le module terminé, **s'arrêter**, donner un compte-rendu au format défini en Partie D.3, et **attendre la validation explicite** avant de passer au module suivant.
4. **Autonomie à l'intérieur d'un module** : ne pas demander confirmation à chaque micro-étape. Regrouper les décisions mineures, exécuter le module de façon autonome, rapporter tout au checkpoint.
5. **Une seule question ciblée** si un point bloquant et critique apparaît (sécurité, argent, suppression de données irréversible) — sinon, prendre la décision la plus raisonnable et la documenter dans le compte-rendu.
6. **Toujours livrer du code testé** — voir Definition of Done, Partie D.2. Un module sans tests n'est pas considéré comme terminé.
7. Ne jamais casser un module précédent déjà validé sans le signaler explicitement.

---

## PARTIE A — Backend Laravel

### A.1 Stack technique

- PHP 8.2 / **Laravel 12** (pas Laravel 13, qui exige PHP 8.3 — non disponible dans l'environnement)
- MySQL 8 (MariaDB via XAMPP en local)
- Laravel Sanctum (authentification API par token)
- Laravel Queues (jobs asynchrones)
- Cache (driver database par défaut, Redis si disponible)
- Pest ou PHPUnit pour les tests
- **PSR-12 strict** pour tout le code PHP

### A.2 Environnement local (Windows 11)

- XAMPP avec PHP 8.2.12, Composer 2.10.2, PHP dans le PATH
- Vérifier la version PHP active (`php -v`) avant tout `composer create-project` — doit être 8.2.x
- Base MySQL via phpMyAdmin/XAMPP, pas de Docker
- Base locale : `gestion_locative` (utf8mb4_unicode_ci), utilisateur `root` sans mot de passe

### A.3 Architecture cible

- Couches : **Controllers → Services → Repositories → Models**
- Validation via **Form Requests** dédiées (jamais de validation inline dans les controllers)
- Sérialisation via **API Resources** (`JsonResource`)
- Toutes les routes préfixées **`/api/v1`**
- Format de réponse JSON standardisé :
```json
{ "success": true, "data": { }, "message": "..." }
```
et pour les erreurs :
```json
{ "success": false, "message": "...", "errors": { } }
```
- Pagination systématique sur les listes (`paginate()`)
- Rôles gérés via un champ `role` sur `users` (`owner` / `tenant` / `admin`) + middlewares/policies pour restreindre l'accès aux ressources (un propriétaire ne voit que ses biens, un locataire que son contrat actif, etc. — voir règles de gestion A.6)

### A.4 Modèle de données — 17 tables

| Table | Rôle | Champs clés |
|---|---|---|
| `users` | Comptes (propriétaire/locataire/admin) | id, name, email (unique), phone (unique), password (hash), role (enum), email_verified_at |
| `profiles` | Infos complémentaires | id, user_id→users, avatar, address, city, id_card_number, id_card_photo, date_of_birth |
| `properties` | Biens immobiliers | id, owner_id→users, title, type (enum: maison/appartement/studio/chambre), address, city, surface_area, rooms_count, monthly_rent, deposit_amount, status (enum: available/rented/maintenance), description |
| `property_images` | Photos d'un bien | id, property_id→properties, image_path, is_primary |
| `property_units` | Unités locatives (immeuble) | id, property_id→properties, unit_name, floor, rooms_count, monthly_rent, status |
| `leases` | Contrats de location (bail) | id, property_id/unit_id, tenant_id→users, owner_id→users, start_date, end_date, monthly_rent, deposit_amount, status (enum: pending/active/terminated/expired), contract_pdf_path |
| `lease_documents` | Documents attachés au bail | id, lease_id→leases, document_type (enum: contract/id_proof/state_of_premises/other), file_path, uploaded_by→users |
| `tenants` | Infos spécifiques locataire | id, user_id→users, guarantor_name, guarantor_phone, occupation, monthly_income |
| `payments` | Paiements de loyer | id, lease_id→leases, tenant_id→users, amount, payment_method_id→payment_methods, payment_date, period_covered, status (enum: pending/validated/late/partial), reference |
| `payment_methods` | Moyens de paiement | id, user_id→users, type (enum: mobile_money/bank_transfer/cash), provider, account_number, is_default |
| `receipts` | Quittances générées | id, payment_id→payments, receipt_number (unique), pdf_path, generated_at |
| `maintenance_requests` | Demandes de maintenance | id, lease_id/property_id, tenant_id→users, title, description, priority (enum: low/medium/high/urgent), status (enum: new/in_progress/resolved/rejected), photo_path |
| `maintenance_comments` | Échanges sur une demande | id, maintenance_request_id→maintenance_requests, user_id→users, comment |
| `expenses` | Charges d'un bien | id, property_id→properties, owner_id→users, category (enum: maintenance/tax/insurance/other), amount, expense_date, description |
| `notifications` | Notifications système | id, user_id→users, type, title, message, is_read |
| `messages` | Messagerie propriétaire↔locataire | id, lease_id/property_id, sender_id→users, receiver_id→users, content, is_read |
| `activity_logs` | Journal d'activité | id, user_id→users, action, description, ip_address |

> Utiliser des migrations Laravel classiques, clés étrangères avec `constrained()->cascadeOnDelete()` où logique, `enum` via colonnes string + validation applicative (plus portable que les enums MySQL natifs).

### A.5 Routes API — 73 endpoints (préfixe `/api/v1`)

**Authentification (10)** — `POST auth/register`, `POST auth/login`, `POST auth/google`, `POST auth/logout`, `POST auth/refresh`, `PUT auth/complete-profile`, `POST auth/forgot-password`, `POST auth/reset-password`, `POST auth/verify-email`, `GET auth/me`

**Utilisateurs & Profils (6)** — `GET users`, `GET users/{id}`, `PUT users/{id}`, `DELETE users/{id}`, `POST users/{id}/avatar`, `PUT users/{id}/password`

**Biens immobiliers (9)** — `GET properties`, `POST properties`, `GET properties/{id}`, `PUT properties/{id}`, `DELETE properties/{id}`, `POST properties/{id}/images`, `DELETE properties/{id}/images/{imageId}`, `GET properties/search`, `GET properties/available`

**Unités locatives (5)** — `GET properties/{id}/units`, `POST properties/{id}/units`, `GET units/{id}`, `PUT units/{id}`, `DELETE units/{id}`

**Contrats de location (8)** — `GET leases`, `POST leases`, `GET leases/{id}`, `PUT leases/{id}`, `DELETE leases/{id}`, `POST leases/{id}/terminate`, `POST leases/{id}/renew`, `GET leases/{id}/download`

**Paiements (8)** — `GET payments`, `POST payments`, `GET payments/{id}`, `PUT payments/{id}`, `DELETE payments/{id}`, `GET payments/history`, `POST payments/initiate`, `GET payments/stats`

**Quittances (3)** — `GET receipts`, `GET receipts/{id}`, `GET receipts/{id}/download`

**Maintenance (7)** — `GET maintenance-requests`, `POST maintenance-requests`, `GET maintenance-requests/{id}`, `PUT maintenance-requests/{id}`, `DELETE maintenance-requests/{id}`, `POST maintenance-requests/{id}/comments`, `PUT maintenance-requests/{id}/status`

**Charges / Dépenses (5)** — `GET expenses`, `POST expenses`, `GET expenses/{id}`, `PUT expenses/{id}`, `DELETE expenses/{id}`

**Messagerie (5)** — `GET conversations`, `GET conversations/{id}/messages`, `POST messages`, `PUT messages/{id}/read`, `DELETE messages/{id}`

**Notifications (4)** — `GET notifications`, `PUT notifications/{id}/read`, `PUT notifications/read-all`, `DELETE notifications/{id}`

**Tableau de bord (4)** — `GET dashboard/owner`, `GET dashboard/tenant`, `GET dashboard/revenue`, `GET dashboard/occupancy`

### A.6 Règles de gestion (à coder en dur dans les Services, pas juste documentées)

1. Un bien (ou une unité) ne peut être lié qu'à un seul contrat de location **actif** à la fois.
2. Le statut du bien passe automatiquement à `rented` dès l'activation du contrat, et revient à `available` à la résiliation/expiration.
3. Un paiement ne peut être enregistré que pour un contrat au statut `active`.
4. La quittance est générée automatiquement **uniquement** pour un paiement au statut `validated` (job en queue).
5. Un locataire ne peut soumettre une demande de maintenance que pour un bien où il a un contrat actif.
6. Un contrat passe automatiquement à `expired` à sa date de fin s'il n'a pas été renouvelé (job planifié / scheduled task).
7. Rappels de paiement automatiques envoyés X jours avant échéance (valeur configurable en `.env` — `PAYMENT_REMINDER_DAYS_BEFORE`).
8. Un utilisateur ne peut consulter que les ressources qui le concernent directement (policies Laravel : propriétaire ↔ ses biens, locataire ↔ ses contrats).
9. Dépôt de garantie obligatoire à la création d'un contrat, conservé dans l'historique du bail.

### A.7 Sécurité

- Sanctum avec expiration et révocation des tokens
- Hash bcrypt sur les mots de passe
- Form Requests sur **toutes** les routes d'écriture
- Throttling sur les routes sensibles (`auth/login`, `auth/register`, `payments/initiate`)
- Policies par ressource (`PropertyPolicy`, `LeasePolicy`, `PaymentPolicy`, etc.)
- CORS restreint à l'origine de l'app mobile

### A.8 Tests attendus par module

- Feature tests sur **chaque** endpoint (cas nominal + cas d'erreur + autorisation refusée)
- Unit tests sur les Services contenant de la logique métier (calcul de statut, génération de quittance, règles A.6)
- Priorité absolue : authentification, paiements, contrats

---

## PARTIE C — Plan de route (phases + checkpoints)

Chaque phase = un module côté backend **et** son équivalent côté frontend (repo `gestion_locative_frontend`). Respecter cet ordre.

| Phase | Backend | Frontend | Checkpoint = |
|---|---|---|---|
| **0. Setup** | Init Laravel 12, config `.env`, migrations vides, health-check route | Init Flutter, structure feature-first, thème (design system), client dio de base | Les deux projets démarrent sans erreur |
| **1. Authentification** | Migrations `users`/`profiles`, Sanctum, 8 routes A.5, tests | Écrans Splash/Login/Register/Forgot password, provider auth, guard go_router | Un compte peut être créé et connecté de bout en bout sur l'appareil |
| **2. Profils** | Routes users/profils (6), upload avatar | Écran Profil (voir/modifier), upload photo | Modification de profil visible en base et sur mobile |
| **3. Biens & Unités** | `properties`, `property_images`, `property_units` + 14 routes | Liste/détail/création de biens, galerie photos | Un propriétaire crée un bien avec photos, visible dans la liste |
| **4. Contrats (baux)** | `leases`, `lease_documents`, `tenants` + 8 routes + génération PDF + règle A.6.1/2 | Liste/détail/création de bail, visualiseur PDF | Un bail actif change le statut du bien automatiquement |
| **5. Paiements** | `payments`, `payment_methods` + 8 routes + règle A.6.3 | Liste, initier un paiement, historique | Un paiement enregistré change le statut du contrat/paiement correctement |
| **6. Quittances** | `receipts` + 3 routes + job async de génération (règle A.6.4) | Liste + téléchargement quittance | Une quittance PDF est générée et téléchargeable après un paiement validé |
| **7. Maintenance** | `maintenance_requests`, `maintenance_comments` + 7 routes + règle A.6.5 | Liste, création, fil de discussion | Un locataire crée une demande visible côté propriétaire |
| **8. Charges/Dépenses** | `expenses` + 5 routes | (intégré au dashboard propriétaire, pas d'écran dédié obligatoire) | Solde net calculable par bien |
| **9. Messagerie** | `messages` + 5 routes | Conversations + écran de discussion | Message envoyé par A visible par B en temps quasi-réel (polling ou refresh) |
| **10. Notifications** | `notifications` + 4 routes + rappels automatiques (règle A.6.7) | Liste des notifications, badge non-lues | Une notification est créée automatiquement lors d'un événement clé (paiement, message...) |
| **11. Tableau de bord** | 4 routes stats/dashboard | Écrans statistiques (graphiques revenus, occupation) | Les chiffres affichés correspondent aux données réelles |
| **12. Finitions** | `activity_logs`, policies globales, revue sécurité, doc API | Gestion d'erreurs globale, états vides/loading soignés, build APK de démo | App utilisable de bout en bout par les deux rôles sans crash |

**État actuel : Phases 0 à 12 terminées et checkpointées (auth, profils, biens, baux, paiements, quittances, maintenance, charges, messagerie, notifications, tableau de bord, finitions). Module 13 — Audit et complétion en cours : voir historique git (commits 'fix(leases)', 'docs') pour le détail des corrections issues de l'audit complet du 20/07/2026.**

---

## PARTIE D — Standards, Git, Definition of Done, format de compte-rendu

### D.1 Convention Git

- Conventional Commits : `feat(scope): ...`, `fix(scope): ...`, `test(scope): ...`, `refactor(scope): ...`, `chore(scope): ...`
- Un commit ne mélange jamais deux modules différents
- Pas de commit avec des tests rouges

### D.2 Definition of Done (par module)

**Backend :** migration + modèle + relations Eloquent + Form Requests + Controller + API Resource + routes enregistrées + policies si nécessaire + tests Feature (verts) + code passé au formatter PSR-12 (`./vendor/bin/pint`).

Un module qui ne remplit pas ces critères n'est **pas** considéré comme terminé, même si "ça marche à l'écran".

### D.3 Format de compte-rendu attendu à chaque checkpoint

```
## Checkpoint — Module X : <nom>

### Résumé
[2-3 phrases sur ce qui a été livré]

### Fichiers créés/modifiés
[liste]

### Comment tester
[commandes exactes à lancer, étapes manuelles si besoin]

### Points d'attention / décisions prises
[hypothèses faites en autonomie, compromis, dette technique éventuelle]

### Prochaine étape proposée
[Module suivant du plan de route]
```

### D.4 Pièges connus de l'environnement

- **Laravel 13** → ne jamais installer, nécessite PHP 8.3 non disponible ici. Rester sur Laravel 12.
- **DB** → MySQL/MariaDB via XAMPP, base `gestion_locative`, pas de Docker.
- **Extension GD** → requise par dompdf pour embarquer des PNG (logo en en-tête des PDF, upload d'images). Est bien activée dans `C:\xampp\php\php.ini` (`extension=gd`, vérifié le 23/07/2026) — si ça régresse un jour, décommenter la ligne et redémarrer PHP/Apache.
- **⚠️ "Impossible de contacter le serveur" depuis le téléphone (résolu le 23/07/2026)** → cause réelle : `php artisan serve` sans option écoute uniquement sur `127.0.0.1` (localhost), donc **injoignable depuis le téléphone en Wi-Fi même avec la bonne IP côté app**. Fix définitif appliqué :
  - `composer.json` (script `dev`) et tout lancement manuel doivent utiliser `php artisan serve --host=0.0.0.0 --port=8000` (jamais `php artisan serve` seul).
  - Script `start-server.ps1` (+ wrapper `start-server.bat`) à la racine du repo : démarre MySQL (XAMPP) si besoin puis lance `artisan serve --host=0.0.0.0`.
  - Raccourci `GestionLocative-Backend.vbs` déposé dans le dossier Démarrage de Windows (`shell:startup`) → lance `start-server.bat` en arrière-plan (sans fenêtre) à **chaque ouverture de session Windows**, donc le backend est up même après un redémarrage complet du PC/fermeture de VS Code. (Tâche planifiée classique impossible à créer : pas de droits d'élévation disponibles dans l'environnement — le fichier de démarrage est l'équivalent sans besoin d'admin.)
  - Règle Pare-feu Windows "Laravel 8000" (Inbound, Allow) déjà présente — ne pas la supprimer.
  - Si l'app renvoie encore cette erreur : vérifier `netstat -ano | findstr :8000` → doit afficher `0.0.0.0:8000`, **pas** `127.0.0.1:8000`.
- **IP locale du PC** → si le PC change de réseau (autre Wi-Fi, partage de connexion), l'IP `192.168.1.77` actuelle changera. Il faudra alors mettre à jour **à la fois** `APP_URL` dans `.env` (backend) et `apiBaseUrl` dans `frontend_API-master/lib/core/network/dio_client.dart`, puis relancer les deux. Ce n'est pas automatisable sans réservation DHCP sur la box/routeur.
- **Google Sign-In** → nécessite un projet Firebase/Google Cloud (inexistant au 20/07/2026) pour fonctionner réellement sur l'appareil : `GOOGLE_CLIENT_ID` dans `.env`, SHA-1 de la clé de signature (debug **et** release) enregistré dans la console Firebase. Le backend (vérification du jeton, migrations, routes) fonctionne indépendamment de cette étape et est entièrement testé.
