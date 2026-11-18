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
        // Who AMS buys outsourced seats from. Admin-only: agencies never see suppliers.
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Whether AMS bought the block itself (direct) or through a supplier (outsourced).
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->string('sourcing', 20)->default('direct')->after('supplier_pnr')->index();
            $table->foreignId('supplier_id')->nullable()->after('sourcing')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flight_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropIndex(['sourcing']);
            $table->dropColumn('sourcing');
        });

        Schema::dropIfExists('suppliers');
    }
};
