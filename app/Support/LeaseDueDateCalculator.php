<?php

namespace App\Support;

use App\Models\Lease;
use Carbon\Carbon;

/**
 * Calcule la prochaine échéance mensuelle d'un bail à partir du jour du
 * mois de sa date de début (pas de champ "due date" dédié dans le
 * schéma). Utilisé à la fois par les rappels de paiement (règle A.6.7)
 * et par le tableau de bord locataire.
 */
class LeaseDueDateCalculator
{
    public static function nextDueDate(Lease $lease, ?Carbon $today = null): Carbon
    {
        $today = ($today ?? Carbon::today())->copy()->startOfDay();

        $day = min($lease->start_date->day, $today->daysInMonth);
        $dueDate = Carbon::create($today->year, $today->month, $day);

        if ($dueDate->lt($today)) {
            $nextMonth = $today->copy()->addMonthNoOverflow();
            $dueDate = Carbon::create($nextMonth->year, $nextMonth->month, min($lease->start_date->day, $nextMonth->daysInMonth));
        }

        return $dueDate;
    }
}
