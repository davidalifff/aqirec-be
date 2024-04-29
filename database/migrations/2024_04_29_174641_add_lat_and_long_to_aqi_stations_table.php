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
        Schema::table('aqi_stations', function (Blueprint $table) {
            $table->string('url_2')->nullable()->after('id');
            $table->string('url_1')->nullable()->after('id');
            $table->decimal('long', 9, 6)->nullable()->after('id');
            $table->decimal('lat', 8, 6)->nullable()->after('id');
            $table->string('nama')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aqi_stations', function (Blueprint $table) {
            //
        });
    }
};
