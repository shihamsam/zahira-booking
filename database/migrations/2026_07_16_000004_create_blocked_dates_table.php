<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            // null = applies to all resources
            $table->foreignId('resource_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_dates');
    }
};
