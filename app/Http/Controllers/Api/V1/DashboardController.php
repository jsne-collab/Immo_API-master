<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaseResource;
use App\Http\Resources\MaintenanceRequestResource;
use App\Http\Resources\PaymentResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly DashboardService $dashboardService) {}

    public function owner(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $user->isAdmin(), 403);

        $stats = $this->dashboardService->ownerStats($user);

        return $this->success([
            'monthly_revenue' => $stats['monthly_revenue'],
            'previous_month_revenue' => $stats['previous_month_revenue'],
            'revenue_variation_percent' => $stats['revenue_variation_percent'],
            'occupancy_rate' => $stats['occupancy_rate'],
            'pending_payments_count' => $stats['pending_payments_count'],
            'available_properties_count' => $stats['available_properties_count'],
            'total_properties_count' => $stats['total_properties_count'],
            'recent_payments' => PaymentResource::collection($stats['recent_payments']),
            'open_maintenance_requests' => MaintenanceRequestResource::collection($stats['open_maintenance_requests']),
        ], '');
    }

    public function tenant(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isTenant() || $user->isAdmin(), 403);

        $stats = $this->dashboardService->tenantStats($user);

        return $this->success([
            'current_lease' => $stats['current_lease'] ? new LeaseResource($stats['current_lease']) : null,
            'next_due_date' => $stats['next_due_date'],
            'recent_payments' => PaymentResource::collection($stats['recent_payments']),
            'open_maintenance_requests' => MaintenanceRequestResource::collection($stats['open_maintenance_requests']),
        ], '');
    }

    public function revenue(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $user->isAdmin(), 403);

        return $this->success($this->dashboardService->revenueSeries($user), '');
    }

    public function occupancy(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isOwner() || $user->isAdmin(), 403);

        return $this->success($this->dashboardService->occupancy($user), '');
    }
}
