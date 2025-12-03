<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique()->comment('Clé unique du paramètre');
            $table->text('valeur')->nullable()->comment('Valeur du paramètre');
            $table->string('type')->default('text')->comment('Type: text, textarea, image, file, json, boolean');
            $table->string('groupe')->nullable()->comment('Groupe de paramètres (ex: contact, social, seo)');
            $table->string('description')->nullable()->comment('Description du paramètre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};