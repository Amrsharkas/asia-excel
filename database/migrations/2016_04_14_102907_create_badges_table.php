<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBadgesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('badges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('quiz_id');
            $table->string('course_type');
            $table->integer('course_id');
            $table->float('grade');
            $table->text('icon');
            $table->text('image');
            $table->timestamps();
        });
        // student badges table

        Schema::create('student_badges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('quiz_id');
            $table->integer('course_id');
            $table->integer('badge_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('badges');
        Schema::drop('student_badges');
    }

}
