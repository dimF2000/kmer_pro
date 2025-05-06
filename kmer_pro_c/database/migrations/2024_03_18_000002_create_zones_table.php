<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('ville');
            $table->string('region')->nullable();
            $table->string('pays')->default('Cameroun');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
}; 