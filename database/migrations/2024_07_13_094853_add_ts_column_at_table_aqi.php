<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aqi', function (Blueprint $table) {
            $table->timestamp('ts')->nullable()->after('aqicn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aqi', function (Blueprint $table) {
            $table->dropColumn('ts');
        });
    }
};
