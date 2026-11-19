<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A group may leave its child and infant fares empty: children then pay the adult fare,
     * and infants can't be booked on it.
     */
    public function up(): void
    {
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->unsignedInteger('child_fare')->nullable()->change();
            $table->unsignedInteger('infant_fare')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('flight_groups')->whereNull('child_fare')->update(['child_fare' => DB::raw('adult_fare')]);
        DB::table('flight_groups')->whereNull('infant_fare')->update(['infant_fare' => 0]);

        Schema::table('flight_groups', function (Blueprint $table) {
            $table->unsignedInteger('child_fare')->nullable(false)->change();
            $table->unsignedInteger('infant_fare')->nullable(false)->change();
        });
    }
};
