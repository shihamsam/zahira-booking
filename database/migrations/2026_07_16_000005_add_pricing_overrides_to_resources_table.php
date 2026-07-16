<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            // JSON map of slot_type → rate overrides, e.g.
            // {"daytime": 7000, "night_4lights": 4000}
            $table->json('pricing_overrides')->nullable()->after('price_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('pricing_overrides');
        });
    }
};
