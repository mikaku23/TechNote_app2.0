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
        Schema::create('vercel_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('sync_status', [
                'pending',
                'success',
                'failed'
            ]);

            $table->longText('response')
                ->nullable();

            $table->timestamp('synced_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vercel_sync_logs');
    }
};
