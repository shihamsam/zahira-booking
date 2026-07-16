<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('nic', 20)->nullable()->after('mobile_number');
            $table->string('email', 255)->nullable()->after('nic');
            // slot_type: which time-slot this booking occupies
            $table->string('slot_type', 30)->nullable()->after('purpose');
            $table->time('start_time')->nullable()->after('slot_type');
            $table->time('end_time')->nullable()->after('start_time');
            $table->unsignedSmallInteger('hours')->nullable()->after('end_time');
            // Azwar Hall add-ons
            $table->unsignedSmallInteger('chair_count')->nullable()->after('hours');
            $table->boolean('sound_system_requested')->default(false)->after('chair_count');
            // Rejection audit trail (mirrors the confirmed_by / cancelled_by pattern)
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('cancellation_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        // Extend the status enum to include 'rejected'.
        // Blueprint::enum()->change() doesn't support enum extension across all
        // Laravel versions, so we use a raw DDL statement.
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'nic', 'email', 'slot_type', 'start_time', 'end_time', 'hours',
                'chair_count', 'sound_system_requested',
                'rejected_by', 'rejected_at', 'rejection_reason',
            ]);
        });

        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
