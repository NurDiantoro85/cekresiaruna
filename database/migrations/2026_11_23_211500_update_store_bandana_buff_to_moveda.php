<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('stores')
            ->where('name', 'like', '%Bandana Buff%')
            ->update([
                'name' => 'Moveda Activewear (TikTok Shop/Tokped)',
                'sender_keywords' => 'bandana buff, bandana buff outdoor, moveda',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('stores')
            ->where('name', 'Moveda Activewear (TikTok Shop/Tokped)')
            ->update([
                'name' => 'Bandana Buff Outdoor Indonesia (TikTok)',
                'sender_keywords' => 'bandana buff, bandana buff outdoor',
            ]);
    }
};
