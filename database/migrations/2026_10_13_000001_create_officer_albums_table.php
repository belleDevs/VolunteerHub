<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officer_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['organization_id', 'year']);
        });

        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('officer_album_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('position');
            $table->string('photo_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officers');
        Schema::dropIfExists('officer_albums');
    }
};
