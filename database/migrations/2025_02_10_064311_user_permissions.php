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
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('can_create_jobs')->default(0);
            $table->boolean('can_create_education')->default(0);
            $table->boolean('can_create_events')->default(0);
            $table->text('can_create_post_category')->nullable();
            $table->boolean('can_review_job_applications')->default(0);
            $table->boolean('can_manage_users')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_permissions');
    }
};
