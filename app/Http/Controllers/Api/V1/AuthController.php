<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token']->plainTextToken,
        ], 'Compte créé avec succès.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('login'),
            $request->validated('password')
        );

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token']->plainTextToken,
        ], 'Connexion réussie.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Déconnexion réussie.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refresh($request->user());

        return $this->success([
            'token' => $token->plainTextToken,
        ], 'Token rafraîchi.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        return $this->success(null, 'Si ce compte existe, un email de réinitialisation a été envoyé.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return $this->success(null, 'Mot de passe réinitialisé avec succès.');
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->authService->verifyEmail(
            $request->validated('email'),
            $request->validated('code')
        );

        return $this->success(new UserResource($user), 'Adresse email vérifiée avec succès.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()), '');
    }

    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithGoogle($request->validated('id_token'));

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token']->plainTextToken,
        ], 'Connexion réussie.');
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $this->authService->completeProfile($request->user(), $request->validated());

        return $this->success(new UserResource($user), 'Profil complété avec succès.');
    }
}
