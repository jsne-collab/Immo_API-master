<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserService
{
    private const AVATAR_DISK = 'public';

    public function __construct(private readonly UserRepository $users) {}

    /**
     * Les admins listent librement (avec filtres facultatifs). Un
     * propriétaire ne peut que rechercher un locataire précis (par nom,
     * email ou téléphone) — jamais parcourir l'ensemble des comptes.
     */
    public function list(User $requester, ?string $search, ?string $role, int $perPage = 15): LengthAwarePaginator
    {
        if ($requester->isAdmin()) {
            return $this->users->paginate(['role' => $role, 'search' => $search], $perPage);
        }

        if (empty($search)) {
            throw ValidationException::withMessages([
                'search' => ['Un terme de recherche est requis (nom, email ou téléphone).'],
            ]);
        }

        return $this->users->paginate(['role' => User::ROLE_TENANT, 'search' => $search], $perPage);
    }

    public function show(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $userFields = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
        $profileFields = array_intersect_key(
            $data,
            array_flip(['address', 'city', 'date_of_birth', 'id_card_number'])
        );

        if ($userFields !== []) {
            $user = $this->users->update($user, $userFields);
        }

        if ($profileFields !== []) {
            $this->users->updateProfile($user, $profileFields);
        }

        return $user->fresh('profile');
    }

    public function delete(User $user): void
    {
        $this->deleteAvatarFile($user);
        $this->users->delete($user);
    }

    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        $this->deleteAvatarFile($user);

        $path = $file->store('avatars', self::AVATAR_DISK);
        $this->users->updateProfile($user, ['avatar' => $path]);

        return $user->fresh('profile');
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();
    }

    private function deleteAvatarFile(User $user): void
    {
        $path = $user->profile?->avatar;

        if ($path) {
            Storage::disk(self::AVATAR_DISK)->delete($path);
        }
    }
}
