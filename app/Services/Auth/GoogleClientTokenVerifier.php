<?php

namespace App\Services\Auth;

use App\Contracts\GoogleIdTokenVerifier;
use Google\Client as GoogleClient;

/**
 * Vérification réelle d'un idToken Google via google/apiclient — contrôle
 * la signature, l'expiration et l'audience (aud doit correspondre au
 * GOOGLE_CLIENT_ID configuré). Ne jamais faire confiance aux données
 * envoyées par le client sans passer par cette vérification serveur.
 */
class GoogleClientTokenVerifier implements GoogleIdTokenVerifier
{
    public function verify(string $idToken): ?array
    {
        $client = new GoogleClient(['client_id' => config('services.google.client_id')]);

        $payload = $client->verifyIdToken($idToken);

        if (! is_array($payload) || empty($payload['email_verified'])) {
            return null;
        }

        return [
            'sub' => (string) $payload['sub'],
            'email' => (string) $payload['email'],
            'name' => (string) ($payload['name'] ?? $payload['email']),
            'picture' => $payload['picture'] ?? null,
        ];
    }
}
