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
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'disponible') && !Schema::hasColumn('services', 'disponibilite')) {
                $table->renameColumn('disponible', 'disponibilite');
            } else if (!Schema::hasColumn('services', 'disponibilite') && !Schema::hasColumn('services', 'disponible')) {
                $table->boolean('disponibilite')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'disponibilite')) {
                $table->dropColumn('disponibilite');
            }
        });
    }
};
