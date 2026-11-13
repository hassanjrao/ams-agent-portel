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
        // A travel agency that buys seats from AMS. Its staff log in as users with the agent role.
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person');
            $table->string('phone', 30);
            $table->string('email');
            $table->string('city', 100);
            $table->string('country', 100)->default('Pakistan');
            $table->string('address')->nullable();
            $table->string('logo_path')->nullable();
            // pending until AMS approves the sign-up; suspended blocks booking but keeps history.
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('agent')->after('email')->index();
            $table->foreignId('agency_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('phone', 30)->nullable()->after('agency_id');
        });

        Schema::table('agencies', function (Blueprint $table) {
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agency_id');
            $table->dropColumn(['role', 'phone']);
        });
        Schema::dropIfExists('agencies');
    }
};
