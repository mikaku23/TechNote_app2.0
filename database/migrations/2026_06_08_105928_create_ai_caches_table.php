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
        Schema::create('ai_cache', function (Blueprint $table) {
            $table->id();

            $table->string('cache_key')
                ->unique();

            $table->longText('question');

            $table->longText('answer');

            $table->string('source');

            $table->timestamp('expired_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_caches');
    }
};
