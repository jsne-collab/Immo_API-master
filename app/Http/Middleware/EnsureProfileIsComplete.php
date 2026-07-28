<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès aux ressources métier tant qu'un utilisateur créé via
 * Google Sign-In n'a pas complété son profil (rôle + téléphone + CGU),
 * en plus de la garde équivalente côté go_router sur le frontend.
 */
class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->profile_completed) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Profil incomplet. Merci de finaliser votre inscription.',
                'errors' => ['profile_completed' => ['Le profil doit être complété avant d\'accéder à cette ressource.']],
            ], 403);
        }

        return $next($request);
    }
}
