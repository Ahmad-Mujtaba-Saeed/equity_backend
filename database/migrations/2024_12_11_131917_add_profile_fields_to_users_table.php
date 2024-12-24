<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('username')->unique()->nullable();
            $table->string('city')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('age_group')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('website_url')->nullable();
            $table->boolean('email_notification')->default(true);
            $table->boolean('sms_notification')->default(true);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'username', 'city', 'gender',
                'date_of_birth', 'marital_status', 'age_group', 'country',
                'state', 'address', 'phone', 'website_url',
                'email_notification', 'sms_notification'
            ]);
        });
    }
};
