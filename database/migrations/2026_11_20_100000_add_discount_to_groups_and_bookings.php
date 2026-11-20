<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A flat discount AMS can put on a group, taken off every booking on it. The booking keeps the
     * amount it got, so changing the group's discount later never reprices a booking already made.
     */
    public function up(): void
    {
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->unsignedInteger('discount_per_booking')->default(0)->after('infant_fare');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('discount_amount')->default(0)->after('infant_fare');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('discount_amount');
        });

        Schema::table('flight_groups', function (Blueprint $table) {
            $table->dropColumn('discount_per_booking');
        });
    }
};
