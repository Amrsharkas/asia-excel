<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description');
            $table->boolean('paid');
            $table->dateTime('closed_by');
            $table->boolean('active');
            $table->timestamps();
        });
        Schema::create('job_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('job_id');
            $table->text('cv_path');
            $table->text('portofolio_path'); //there are additional_files too
            $table->timestamps();
        });
        Schema::create('job_application_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('job_application_id');
            $table->text('path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('jobs');
        Schema::drop('job_applications');
        Schema::drop('job_application_files');
    }

}
