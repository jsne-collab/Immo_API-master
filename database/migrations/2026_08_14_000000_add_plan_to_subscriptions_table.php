<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Formule choisie par le propriétaire (mensuel 10 000 FCFA /
            // annuel 50 000 FCFA, voir config/subscription.php). Par défaut
            // "monthly" pour les enregistrements déjà en base avant ce choix
            // (audit du 13/08/2026).
            $table->enum('plan', ['monthly', 'yearly'])->default('monthly')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
