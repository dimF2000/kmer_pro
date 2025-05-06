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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('professionnel_id')->constrained('users')->onDelete('cascade');
            $table->decimal('montant', 10, 2);
            $table->string('devise', 3)->default('XAF');
            $table->enum('statut', ['en_attente', 'confirme', 'annule', 'complete'])->default('en_attente');
            $table->string('methode')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('date_confirmation')->nullable();
            $table->timestamp('date_annulation')->nullable();
            $table->text('commentaire')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
}; 