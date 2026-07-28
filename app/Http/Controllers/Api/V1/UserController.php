<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\DeleteUserRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list($request->user(), $request->query('search'), $request->query('role'));

        return $this->paginated($users, UserResource::class);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(new UserResource($this->userService->show($user->id)), '');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->update($user, $request->validated());

        return $this->success(new UserResource($updated), 'Profil mis à jour avec succès.');
    }

    public function destroy(DeleteUserRequest $request, User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->success(null, 'Compte supprimé avec succès.');
    }

    public function uploadAvatar(UploadAvatarRequest $request, User $user): JsonResponse
    {
        $updated = $this->userService->uploadAvatar($user, $request->file('avatar'));

        return $this->success(new UserResource($updated), 'Photo de profil mise à jour.');
    }

    public function updatePassword(UpdatePasswordRequest $request, User $user): JsonResponse
    {
        $this->userService->updatePassword(
            $user,
            $request->validated('current_password'),
            $request->validated('password')
        );

        return $this->success(null, 'Mot de passe mis à jour avec succès.');
    }
}
