<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('categorie_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->constrained()->onDelete('cascade');
            $table->string('titre');
            $table->text('description');
            $table->decimal('prix', 10, 2);
            $table->boolean('disponible')->default(true);
            $table->decimal('note_moyenne', 2, 1)->default(0);
            $table->integer('nombre_avis')->default(0);
            $table->boolean('disponibilite')->default(true);
            $table->json('zones_couvertes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
}; 