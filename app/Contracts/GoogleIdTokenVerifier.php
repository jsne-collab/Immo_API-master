<?php

namespace App\Contracts;

interface GoogleIdTokenVerifier
{
    /**
     * Vérifie un idToken Google (signature, expiration, audience) et
     * retourne son payload décodé, ou null si le jeton est invalide.
     *
     * @return array{sub: string, email: string, name: string, picture: ?string}|null
     */
    public function verify(string $idToken): ?array;
}
