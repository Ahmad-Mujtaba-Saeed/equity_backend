<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCvToJobApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eq_jobs_app', function (Blueprint $table) {
            $table->string('cv_file_path')->nullable()->after('job_title')->comment('Path to uploaded CV file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('eq_jobs_app', function (Blueprint $table) {
            $table->dropColumn('cv_file_path');
        });
    }
}