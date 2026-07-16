<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();

            // Person making the booking (no user account required)
            $table->string('full_name');
            $table->string('mobile_number');
            $table->string('purpose');

            $table->decimal('total_amount', 10, 2)->default(0);

            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            $table->string('receipt_path')->nullable();

            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
