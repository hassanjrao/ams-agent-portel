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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // Shown to agents and printed on tickets, e.g. AMS-26110001.
            $table->string('reference', 20)->unique();
            $table->foreignId('agency_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('flight_group_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->unsignedSmallInteger('infants')->default(0);
            // Seats this booking takes from the block (infants travel on a lap, see BookingService).
            $table->unsignedSmallInteger('seats');
            // Fares copied from the group when booked, so a later price change never alters this booking.
            $table->unsignedInteger('adult_fare');
            $table->unsignedInteger('child_fare');
            $table->unsignedInteger('infant_fare');
            $table->unsignedBigInteger('total_amount');
            $table->string('status', 20)->default('on_hold')->index();
            $table->timestamp('hold_expires_at')->nullable()->index();
            $table->string('pnr', 20)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->text('agent_remarks')->nullable();
            $table->timestamps();

            $table->index(['flight_group_id', 'status']);
            $table->index(['agency_id', 'created_at']);
        });

        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // adult, child, infant
            $table->string('title', 10);
            $table->string('given_name', 100);
            $table->string('surname', 100);
            $table->string('passport_number', 20)->nullable();
            $table->string('nationality', 60)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('ticket_number', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_passengers');
        Schema::dropIfExists('bookings');
    }
};
