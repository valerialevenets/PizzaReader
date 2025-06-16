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
        Schema::create('manga_metadata', function (Blueprint $table) {
            $table->id();
            $table->integer('anilist_id')->nullable()->unsigned()->index()->unique();
            $table->integer('mal_id')->nullable()->unsigned()->index()->unique();
            $table->string('title_english')->nullable()->index();
            $table->string('title_romaji')->nullable()->index();
            $table->integer('chapters')->nullable()->unsigned();
            $table->integer('volumes')->nullable()->unsigned();
            $table->text('description')->nullable();
            $table->boolean('is_adult')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mangas');
    }
};
