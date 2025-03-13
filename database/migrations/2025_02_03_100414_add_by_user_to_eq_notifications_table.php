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
        Schema::table('eq_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('by_user')->nullable()->after('user_id');
            $table->foreign('by_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eq_notifications', function (Blueprint $table) {
            $table->dropForeign(['by_user']);
            $table->dropColumn('by_user');
        });
    }
};
