<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use App\Support\LeaseDueDateCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_TTL_SECONDS = 300;

    private const REVENUE_MONTHS = 6;

    public static function ownerCacheKey(int $ownerId): string
    {
        return "dashboard:owner:{$ownerId}";
    }

    public static function revenueCacheKey(int $ownerId): string
    {
        return "dashboard:revenue:{$ownerId}";
    }

    /**
     * @return array<string, mixed>
     */
    public function ownerStats(User $owner): array
    {
        return Cache::remember(self::ownerCacheKey($owner->id), self::CACHE_TTL_SECONDS, function () use ($owner) {
            $leaseIds = Lease::where('owner_id', $owner->id)->pluck('id');

            $monthStart = Carbon::now()->startOfMonth();
            $previousMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
            $previousMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();

            $monthlyRevenue = (float) Payment::whereIn('lease_id', $leaseIds)
                ->where('status', 'validated')
                ->where('payment_date', '>=', $monthStart)
                ->sum('amount');

            $previousMonthRevenue = (float) Payment::whereIn('lease_id', $leaseIds)
                ->where('status', 'validated')
                ->whereBetween('payment_date', [$previousMonthStart, $previousMonthEnd])
                ->sum('amount');

            $totalProperties = Property::where('owner_id', $owner->id)->count();
            $rentedProperties = Property::where('owner_id', $owner->id)->where('status', Property::STATUS_RENTED)->count();
            $availableProperties = Property::where('owner_id', $owner->id)->where('status', Property::STATUS_AVAILABLE)->count();

            $recentPayments = Payment::whereIn('lease_id', $leaseIds)
                ->with(['lease.property', 'lease.owner', 'tenant', 'paymentMethod'])
                ->latest('payment_date')
                ->limit(5)
                ->get();

            $openMaintenanceRequests = MaintenanceRequest::whereIn('lease_id', $leaseIds)
                ->whereIn('status', [MaintenanceRequest::STATUS_NEW, MaintenanceRequest::STATUS_IN_PROGRESS])
                ->with(['property', 'lease', 'tenant'])
                ->latest()
                ->limit(5)
                ->get();

            return [
                'monthly_revenue' => $monthlyRevenue,
                'previous_month_revenue' => $previousMonthRevenue,
                'revenue_variation_percent' => $this->variationPercent($monthlyRevenue, $previousMonthRevenue),
                'occupancy_rate' => $totalProperties > 0 ? round($rentedProperties / $totalProperties * 100, 1) : 0.0,
                'pending_payments_count' => Payment::whereIn('lease_id', $leaseIds)->where('status', 'pending')->count(),
                'available_properties_count' => $availableProperties,
                'total_properties_count' => $totalProperties,
                'recent_payments' => $recentPayments,
                'open_maintenance_requests' => $openMaintenanceRequests,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function tenantStats(User $tenant): array
    {
        $lease = Lease::where('tenant_id', $tenant->id)
            ->where('status', Lease::STATUS_ACTIVE)
            ->with(['property', 'owner', 'unit', 'tenant'])
            ->latest()
            ->first();

        $recentPayments = Payment::where('tenant_id', $tenant->id)
            ->with(['lease.property', 'lease.owner', 'tenant', 'paymentMethod'])
            ->latest('payment_date')
            ->limit(5)
            ->get();

        $openMaintenanceRequests = MaintenanceRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', [MaintenanceRequest::STATUS_NEW, MaintenanceRequest::STATUS_IN_PROGRESS])
            ->with(['property', 'lease', 'tenant'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'current_lease' => $lease,
            'next_due_date' => $lease ? LeaseDueDateCalculator::nextDueDate($lease)->toDateString() : null,
            'recent_payments' => $recentPayments,
            'open_maintenance_requests' => $openMaintenanceRequests,
        ];
    }

    /**
     * Série des revenus validés des `REVENUE_MONTHS` derniers mois
     * (mois courant inclus), un point par mois même sans paiement.
     *
     * @return array<int, array<string, mixed>>
     */
    public function revenueSeries(User $owner): array
    {
        return Cache::remember(self::revenueCacheKey($owner->id), self::CACHE_TTL_SECONDS, function () use ($owner) {
            $leaseIds = Lease::where('owner_id', $owner->id)->pluck('id');
            $since = Carbon::now()->subMonths(self::REVENUE_MONTHS - 1)->startOfMonth();

            // Regroupement par mois fait en PHP (plutôt qu'un DATE_FORMAT
            // SQL) pour rester portable entre MySQL (production) et
            // SQLite (tests) — le volume par propriétaire reste faible.
            $totalsByMonth = Payment::whereIn('lease_id', $leaseIds)
                ->where('status', 'validated')
                ->where('payment_date', '>=', $since)
                ->get(['amount', 'payment_date'])
                ->groupBy(fn (Payment $payment) => $payment->payment_date->format('Y-m'))
                ->map(fn ($payments) => (float) $payments->sum('amount'));

            $series = [];
            for ($i = self::REVENUE_MONTHS - 1; $i >= 0; $i--) {
                $monthKey = Carbon::now()->subMonths($i)->format('Y-m');
                $series[] = [
                    'month' => $monthKey,
                    'total' => (float) ($totalsByMonth[$monthKey] ?? 0),
                ];
            }

            return $series;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function occupancy(User $owner): array
    {
        $properties = Property::where('owner_id', $owner->id)->get(['id', 'title', 'status']);
        $total = $properties->count();
        $rented = $properties->where('status', Property::STATUS_RENTED)->count();

        return [
            'overall_rate' => $total > 0 ? round($rented / $total * 100, 1) : 0.0,
            'properties' => $properties,
        ];
    }

    private function variationPercent(float $current, float $previous): float
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }
}
