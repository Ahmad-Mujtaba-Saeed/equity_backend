<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // First, drop the existing password column
            $table->dropColumn('password');
        });

        Schema::table('users', function (Blueprint $table) {
            // Recreate the password column as nullable
            $table->string('password')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the nullable password column
            $table->dropColumn('password');
        });

        Schema::table('users', function (Blueprint $table) {
            // Recreate the password column as required
            $table->string('password');
        });
    }
};
