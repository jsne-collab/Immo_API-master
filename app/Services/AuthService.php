<?php

namespace App\Services;

use App\Contracts\GoogleIdTokenVerifier;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\EmailVerificationOtp;
use App\Repositories\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    private const OTP_CACHE_TTL_MINUTES = 10;

    public function __construct(
        private readonly UserRepository $users,
        private readonly ActivityLogService $activityLog,
        private readonly GoogleIdTokenVerifier $googleVerifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: NewAccessToken}
     */
    public function register(array $data): array
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'profile_completed' => true,
        ]);

        event(new Registered($user));
        $this->sendEmailVerificationOtp($user);
        $this->activityLog->log($user, ActivityLog::ACTION_REGISTER, "Inscription ({$user->role}).");

        return [
            'user' => $user,
            'token' => $user->createToken('mobile'),
        ];
    }

    /**
     * @return array{user: User, token: NewAccessToken}
     */
    public function login(string $login, string $password): array
    {
        $user = $this->users->findByEmailOrPhone($login);

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->activityLog->log($user, ActivityLog::ACTION_LOGIN_FAILED, "Tentative de connexion échouée ({$login}).");

            throw ValidationException::withMessages([
                'login' => ['Identifiants invalides.'],
            ]);
        }

        $this->activityLog->log($user, ActivityLog::ACTION_LOGIN, 'Connexion réussie.');

        return [
            'user' => $user,
            'token' => $user->createToken('mobile'),
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        $this->activityLog->log($user, ActivityLog::ACTION_LOGOUT, 'Déconnexion.');
    }

    public function refresh(User $user): NewAccessToken
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return $user->createToken('mobile');
    }

    public function sendPasswordResetLink(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user && $user->isGoogleOnly()) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte utilise la connexion Google. Connectez-vous avec le bouton "Continuer avec Google".'],
            ]);
        }

        Password::sendResetLink(['email' => $email]);
    }

    /**
     * @return array{user: User, token: NewAccessToken}
     */
    public function loginWithGoogle(string $idToken): array
    {
        $payload = $this->googleVerifier->verify($idToken);

        if ($payload === null) {
            throw new AuthenticationException('Jeton Google invalide.');
        }

        $user = $this->users->findByGoogleId($payload['sub']);

        if (! $user) {
            // Cas liaison de compte : un utilisateur inscrit par mot de passe
            // se connecte via Google avec le même email — un seul compte,
            // jamais de doublon.
            $user = $this->users->findByEmail($payload['email']);

            if ($user) {
                $user->forceFill([
                    'google_id' => $payload['sub'],
                    'avatar_url' => $payload['picture'],
                ])->save();

                $this->activityLog->log($user, ActivityLog::ACTION_LOGIN, 'Connexion réussie (Google, compte lié).');
            } else {
                $user = $this->users->create([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'google_id' => $payload['sub'],
                    'avatar_url' => $payload['picture'],
                    'email_verified_at' => now(),
                    'profile_completed' => false,
                ]);

                event(new Registered($user));
                $this->activityLog->log($user, ActivityLog::ACTION_REGISTER, 'Inscription via Google.');
            }
        } else {
            $this->activityLog->log($user, ActivityLog::ACTION_LOGIN, 'Connexion réussie (Google).');
        }

        return [
            'user' => $user,
            'token' => $user->createToken('mobile'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function completeProfile(User $user, array $data): User
    {
        $user->forceFill([
            'role' => $data['role'],
            'phone' => $data['phone'],
            'profile_completed' => true,
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ])->save();

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resetPassword(array $data): void
    {
        $resetUser = null;

        $status = Password::reset(
            $data,
            function (User $user, string $password) use (&$resetUser): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $this->activityLog->log($resetUser, ActivityLog::ACTION_PASSWORD_RESET, 'Mot de passe réinitialisé.');
    }

    public function sendEmailVerificationOtp(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put(
            $this->otpCacheKey($user->email),
            $code,
            now()->addMinutes(self::OTP_CACHE_TTL_MINUTES)
        );

        $user->notify(new EmailVerificationOtp($code));
    }

    public function verifyEmail(string $email, string $code): User
    {
        $cacheKey = $this->otpCacheKey($email);
        $expected = Cache::get($cacheKey);

        if (! $expected || ! Str::of($expected)->exactly($code)) {
            throw ValidationException::withMessages([
                'code' => ['Code de vérification invalide ou expiré.'],
            ]);
        }

        $user = $this->users->findByEmail($email);

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur introuvable.'],
            ]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        Cache::forget($cacheKey);

        return $user;
    }

    private function otpCacheKey(string $email): string
    {
        return "email_verification_otp:{$email}";
    }
}
