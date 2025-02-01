<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('type', ['text', 'image', 'video', 'file'])->default('text')->after('content');
            $table->string('file_name')->nullable()->after('type');
            $table->string('file_path')->nullable()->after('file_name');
            $table->string('file_size')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('file_size');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'file_name', 'file_path', 'file_size', 'mime_type']);
        });
    }
};
