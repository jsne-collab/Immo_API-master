<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['maison', 'appartement', 'studio', 'chambre']);
            $table->string('address');
            $table->string('city');
            $table->decimal('surface_area', 8, 2)->nullable();
            $table->unsignedSmallInteger('rooms_count')->default(1);
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit_amount', 12, 2);
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
