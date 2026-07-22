<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_dates', function (Blueprint $table) {
            // Stores the specific 1-hour slot number (18–29 scale, where 24 = midnight)
            // for night bookings. Null for daytime / full-day slots.
            $table->smallInteger('slot_hour')->nullable()->after('slot_type');
        });
    }

    public function down(): void
    {
        Schema::table('booking_dates', function (Blueprint $table) {
            $table->dropColumn('slot_hour');
        });
    }
};
