<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('professionnel_id')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'en_cours', 'terminee', 'annulee', 'complete'])->default('en_attente');
            $table->text('description');
            $table->dateTime('date_souhaitee');
            $table->string('adresse');
            $table->decimal('montant', 10, 2)->nullable();
            $table->dateTime('date_acceptation')->nullable();
            $table->dateTime('date_fin')->nullable();
            $table->integer('note')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
}; 