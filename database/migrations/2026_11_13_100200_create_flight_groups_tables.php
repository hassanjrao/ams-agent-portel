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
        // A block of seats AMS bought on fixed flights, sold to agencies seat by seat.
        // ("groups" is a reserved word in MySQL, hence flight_groups.)
        Schema::create('flight_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('airline_id')->constrained()->restrictOnDelete();
            // Display route, e.g. MUX-JED-MUX. The legs below hold the actual flights.
            $table->string('sector', 60)->index();
            // First leg's date, kept here so listings sort and filter without joining legs.
            $table->date('departure_date')->index();
            $table->date('return_date')->nullable();
            // Days on the ground for Umrah groups (15 / 21 / 28); null for one-way groups.
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('total_seats');
            // Whole rupees per passenger.
            $table->unsignedInteger('adult_fare');
            $table->unsignedInteger('child_fare');
            $table->unsignedInteger('infant_fare');
            // The airline's PNR for the block: shown to admins only.
            $table->string('supplier_pnr', 20)->nullable();
            // Minutes an unpaid booking holds its seats; null uses config('portal.hold_minutes').
            $table->unsignedSmallInteger('hold_minutes')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'departure_date']);
        });

        Schema::create('flight_group_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('flight_number', 10);
            $table->string('origin', 3);
            $table->string('destination', 3);
            // Local airport times, as printed on the ticket.
            $table->dateTime('departs_at');
            $table->dateTime('arrives_at');
            // Free text as airlines quote it: "23+7", "20+5", "2 PCS".
            $table->string('baggage', 30)->nullable();
            $table->boolean('meal')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_group_legs');
        Schema::dropIfExists('flight_groups');
    }
};
