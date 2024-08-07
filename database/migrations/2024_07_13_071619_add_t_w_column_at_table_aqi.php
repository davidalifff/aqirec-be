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
            $table->decimal('t')->nullable()->after('index_2');
            $table->decimal('w')->nullable()->after('t');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aqi', function (Blueprint $table) {
            $table->dropColumn('t');
            $table->dropColumn('w');
        });
    }
};
