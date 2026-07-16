<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add slot_type column if not already present (guard against partial runs).
        if (! Schema::hasColumn('booking_dates', 'slot_type')) {
            Schema::table('booking_dates', function (Blueprint $table) {
                $table->string('slot_type', 30)->nullable()->after('date');
            });
        }

        // Add the slot-aware index first (so the FK on resource_id still has
        // an index before the old one is dropped), but only if it doesn't exist.
        $existingIndexes = collect(DB::select("SHOW INDEX FROM booking_dates"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        $newIndex = 'booking_dates_resource_id_date_slot_type_index';
        $oldIndex = 'booking_dates_resource_id_date_index';

        if (! in_array($newIndex, $existingIndexes, true)) {
            Schema::table('booking_dates', function (Blueprint $table) {
                $table->index(['resource_id', 'date', 'slot_type']);
            });
        }

        if (in_array($oldIndex, $existingIndexes, true)) {
            Schema::table('booking_dates', function (Blueprint $table) use ($oldIndex) {
                $table->dropIndex($oldIndex);
            });
        }
    }

    public function down(): void
    {
        $existingIndexes = collect(DB::select("SHOW INDEX FROM booking_dates"))
            ->pluck('Key_name')
            ->unique()
            ->all();

        $newIndex = 'booking_dates_resource_id_date_slot_type_index';
        $oldIndex = 'booking_dates_resource_id_date_index';

        if (! in_array($oldIndex, $existingIndexes, true)) {
            Schema::table('booking_dates', function (Blueprint $table) {
                $table->index(['resource_id', 'date']);
            });
        }

        if (in_array($newIndex, $existingIndexes, true)) {
            Schema::table('booking_dates', function (Blueprint $table) use ($newIndex) {
                $table->dropIndex($newIndex);
            });
        }

        if (Schema::hasColumn('booking_dates', 'slot_type')) {
            Schema::table('booking_dates', function (Blueprint $table) {
                $table->dropColumn('slot_type');
            });
        }
    }
};
