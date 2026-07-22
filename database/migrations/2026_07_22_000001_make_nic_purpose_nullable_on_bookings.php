<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('nic', 20)->nullable()->change();
            $table->string('purpose', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('nic', 20)->nullable(false)->change();
            $table->string('purpose', 255)->nullable(false)->change();
        });
    }
};
