<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            // SQLite (tests) ne verrouille pas réellement les valeurs enum au niveau colonne.
            return;
        }

        DB::statement("ALTER TABLE leases MODIFY status ENUM('active', 'terminated', 'expired') DEFAULT 'active'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE leases MODIFY status ENUM('pending', 'active', 'terminated', 'expired') DEFAULT 'active'");
    }
};
