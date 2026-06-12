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
        Schema::create('rekaps', function (Blueprint $table) {
            $table->id();

            $table->date('rekap_date')->unique();

            $table->unsignedInteger('total_installations')->default(0);
            $table->unsignedInteger('total_repairs')->default(0);

            $table->unsignedInteger('completed_tickets')->default(0);
            $table->unsignedInteger('failed_tickets')->default(0);
            $table->unsignedInteger('pending_tickets')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekaps');
    }
};
