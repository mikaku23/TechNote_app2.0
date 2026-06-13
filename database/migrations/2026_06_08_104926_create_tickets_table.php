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

            $table->uuid('qr_token')
                ->unique();


            $table->string('qr_code')->nullable();
            
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
                'failed',
                'cancelled'
            ])->default('waiting');

            $table->enum('priority', [
                'normal',
                'high',
                'urgent'
            ])->default('normal');

            $table->timestamp('estimated_finish')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->boolean('is_public')->default(true);

            $table->date('booking_date')->nullable();
            $table->enum('session', ['morning', 'afternoon'])->nullable();
            $table->unsignedInteger('queue_number')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();

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
