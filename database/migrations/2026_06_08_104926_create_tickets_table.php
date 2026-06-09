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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('ticket_number')->unique();

            $table->enum('type', [
                'installation',
                'repair'
            ]);

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', [
                'waiting',
                'diagnosis',
                'processing',
                'testing',
                'completed',
                'failed'
            ])->default('waiting');

            $table->enum('priority', [
                'normal',
                'high',
                'urgent'
            ])->default('normal');

            $table->timestamp('estimated_finish')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->boolean('is_public')->default(true);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
