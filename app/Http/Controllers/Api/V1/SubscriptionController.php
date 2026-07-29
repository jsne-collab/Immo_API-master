<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\InitiateSubscriptionRequest;
use App\Http\Resources\LeaseResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserResource;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Statut de l'abonnement du propriétaire connecté.
     */
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $status = $this->subscriptions->statusFor($request->user());

        return $this->success([
            'status' => $status['status'],
            'next_due_date' => $status['next_due_date'],
            'current' => $status['current'] ? new SubscriptionResource($status['current']) : null,
            'amount' => (float) config('subscription.amount'),
        ], '');
    }

    /**
     * Initie le paiement du prochain abonnement — validé ensuite par un
     * admin (voir [validate]), même flux que les paiements de loyer.
     */
    public function initiate(InitiateSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptions->initiate($request->user(), $request->validated());

        return $this->success(
            new SubscriptionResource($subscription),
            'Paiement initié, en attente de validation.',
            201,
        );
    }

    /**
     * Valide un paiement d'abonnement en attente (admin uniquement).
     */
    public function validateSubscription(Request $request, Subscription $subscription): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return $this->success(
            new SubscriptionResource($this->subscriptions->validate($subscription)),
            'Abonnement validé.',
        );
    }

    /**
     * Détail d'un propriétaire pour l'admin : ses locataires (baux actifs)
     * et l'historique de ses paiements d'abonnement.
     */
    public function ownerDetail(Request $request, User $owner): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($owner->isOwner(), 404);

        $detail = $this->subscriptions->ownerDetail($owner);

        return $this->success([
            'owner' => new UserResource($detail['owner']),
            'leases' => LeaseResource::collection($detail['leases']),
            'subscriptions' => SubscriptionResource::collection($detail['subscriptions']),
            'subscription_status' => $detail['subscription_status'],
            'next_due_date' => $detail['next_due_date'],
        ], '');
    }

    /**
     * Vue d'ensemble admin : tous les propriétaires, avec ou sans locataire,
     * et le statut de leurs droits d'utilisation de l'app.
     */
    public function adminOverview(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $overview = array_map(
            fn (array $row) => [
                'owner' => new UserResource($row['owner']),
                'properties_count' => $row['properties_count'],
                'tenant_count' => $row['tenant_count'],
                'subscription_status' => $row['subscription_status'],
                'next_due_date' => $row['next_due_date'],
            ],
            $this->subscriptions->adminOverview(),
        );

        return $this->success($overview, '');
    }
}
