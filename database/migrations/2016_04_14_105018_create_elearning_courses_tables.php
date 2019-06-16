<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateElearningCoursesTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('elearning_courses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->nullable();
            $table->string('title')->nullable();
            $table->text('section_image')->nullable();
            $table->text('course_image')->nullable();
            $table->text('description_overall')->nullable();
            $table->text('description_section')->nullable();
            $table->text('description_course')->nullable();
            $table->float('estimated_time')->nullable();
            $table->integer('prerequisite_id')->nullable(); //elearning_courses.id
            $table->integer('free_samples_count')->nullable(); // boolean 
            $table->timestamps();
        });
        Schema::create('elearning_unlocks', function (Blueprint $table) {
            /*
             * if extend is true then period is in days
             * otherwise period is in weeks
             */
            $table->increments('id');
            $table->integer('course_id')->nullable();
            $table->boolean('extend')->nullable();
            $table->string('period_unit')->nullable();
            $table->integer('period_value')->nullable();
            $table->integer('price')->nullable();
            $table->timestamps();
        });
        Schema::create('elearning_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable();
            $table->integer('stuff_order')->nullable();
            $table->string('title')->nullable();
            $table->string('content_title')->nullable();
            $table->text('content')->nullable(); //change to string if it's just a path 
            $table->string('material_title')->nullable();
            $table->text('material')->nullable();
            $table->boolean('free_sample')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('quiz_id')->nullable();
            $table->timestamps();
        });
        Schema::create('elearning_lessons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_id')->nullable();
            $table->integer('stuff_order')->nullable();
            $table->string('title')->nullable();
            $table->string('content_title')->nullable();
            $table->text('content')->nullable(); //change to string if it's just a path 
            $table->string('material_title')->nullable();
            $table->text('material')->nullable();
            $table->boolean('free_sample')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('quiz_id')->nullable();
            $table->timestamps();
        });
        Schema::create('elearning_topics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lesson_id')->nullable();
            $table->integer('stuff_order')->nullable();
            $table->string('title')->nullable();
            $table->string('content_title')->nullable();
            $table->text('content')->nullable(); //change to string if it's just a path 
            $table->string('material_title')->nullable();
            $table->text('material')->nullable();
            $table->boolean('free_sample')->nullable();
            $table->integer('weight')->nullable();
            $table->integer('quiz_id')->nullable();
            $table->timestamps();
        });
//        Schema::create('elearning_contents', function (Blueprint $table) {
//            /*
//             * type: module - lesson - topic
//             */
//            $table->increments('id');
//            $table->integer('course_id')->nullable();
//            $table->integer('parent_id')->nullable();
//            $table->string('type')->nullable();
//            $table->string('title')->nullable();
//            $table->string('content_title')->nullable();
//            $table->text('content')->nullable(); //change to string if it's just a path 
//            $table->string('material_title')->nullable();
//            $table->text('material')->nullable();
//            $table->timestamps();
//        });
        Schema::create('elearning_students', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable();
            // by default = start_date + unlock period and updated when student extends 
            $table->date('end_date')->nullable();
            $table->boolean('locked')->nullable();
            // if the user clicked try now it appears in his dashboard
            $table->boolean('trial')->nullable();

            $table->timestamps();
        });
        Schema::create('elearning_students_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entry_id'); //elearning_students.id
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable();
            $table->float('progress')->nullable();
            $table->boolean('locked')->nullable();
            $table->timestamps();
        });
        Schema::create('elearning_students_lessons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entry_id'); //elearning_students.id
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable();
            $table->integer('module_id')->nullable(); // elearning_students_modules.id
            $table->float('progress')->nullable();
            $table->boolean('locked')->nullable();
            $table->timestamps();
        });
        Schema::create('elearning_students_topics', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entry_id'); //elearning_students.id
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable();
            $table->integer('module_id')->nullable(); // elearning_students_modules.id
            $table->integer('lesson_id')->nullable(); // elearning_students_lessons.id
            $table->float('progress')->nullable();
            $table->boolean('locked')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('elearning_courses');
        Schema::drop('elearning_unlocks');
        Schema::drop('elearning_modules');
        Schema::drop('elearning_lessons');
        Schema::drop('elearning_topics');
//        Schema::drop('elearning_contents');
        Schema::drop('elearning_students');
        Schema::drop('elearning_students_modules');
        Schema::drop('elearning_students_lessons');
        Schema::drop('elearning_students_topics');
    }

}
