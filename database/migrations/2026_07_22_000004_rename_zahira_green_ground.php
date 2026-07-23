<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('resources')
            ->where('slug', 'zahira-green-ground')
            ->update(['name' => 'Zahira Green']);
    }

    public function down(): void
    {
        DB::table('resources')
            ->where('slug', 'zahira-green-ground')
            ->update(['name' => 'Zahira Green Ground']);
    }
};
