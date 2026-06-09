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
        Schema::create('ai_action_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ai_log_id')
                ->nullable()
                ->constrained('ai_logs')
                ->nullOnDelete();

            $table->string('action_type');

            $table->longText('action_data');

            $table->enum('result', [
                'success',
                'failed',
                'blocked'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_action_logs');
    }
};
