<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('education_contents', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id');
            $table->longText('media')->nullable()->after('video_url');
            // $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('education_contents', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'media']);
        });
    }
};