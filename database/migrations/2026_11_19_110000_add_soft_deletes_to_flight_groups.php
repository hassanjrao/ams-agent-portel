<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deleting a group from the admin only hides it: the row stays, so past bookings keep their group.
     */
    public function up(): void
    {
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
