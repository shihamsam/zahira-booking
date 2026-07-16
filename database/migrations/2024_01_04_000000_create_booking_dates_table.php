<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();

            // A resource can only ever have ONE row for a given date among
            // active (pending/confirmed) bookings. We enforce the "is this
            // date already taken" rule in the BookingService using a
            // pessimistic lock rather than a DB unique constraint, because a
            // cancelled booking's dates must remain in this table for
            // historical/reporting purposes while freeing up the date again.
            $table->index(['resource_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_dates');
    }
};
