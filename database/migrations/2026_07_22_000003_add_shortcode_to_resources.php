<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('shortcode', 10)->nullable()->after('slug');
        });

        // Backfill known resources.
        DB::table('resources')->where('slug', 'zahira-green-ground')->update(['shortcode' => 'ZGG']);
        DB::table('resources')->where('slug', 'azwar-hall')->update(['shortcode' => 'AZW']);
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('shortcode');
        });
    }
};
