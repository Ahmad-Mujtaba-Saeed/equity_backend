<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eq_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('foreign_id');
            $table->string('notif_type');
            $table->text('content')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Index for faster queries
            $table->index(['user_id', 'is_read']);
            $table->index(['notif_type', 'foreign_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('eq_notifications');
    }
};
