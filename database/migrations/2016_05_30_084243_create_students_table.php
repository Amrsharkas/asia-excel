<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('students', function (Blueprint $table) {
            $table->increments('id');
//            $table->string('arabic_proficiency')->nullable();
            $table->boolean('teacher_training')->nullable();
//            $table->boolean('read_arabic')->nullable();
//            $table->boolean('speak_arabic')->nullable();
//            $table->boolean('daylight_saving')->nullable();
//            $table->integer('read_arabic_level')->nullable();
//            $table->string('tajweed_level')->nullable();
//            $table->string('time_difference')->nullable();
//            $table->integer('time_offset')->nullable();
            $table->boolean('revert')->nullable();
            $table->integer('retention_time')->nullable();
            $table->string('reg_code')->nullable();
            $table->boolean('read_arabic')->nullable();
            $table->boolean('speak_arabic')->nullable();
            $table->string('level_of_tajweed')->nullable();
            $table->string('entry_memorization_level')->nullable();
            $table->string('payment_contact')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->integer('account_id');
            $table->integer('user_id');
//            $table->integer('general_row_num');
            $table->timestamps();
        });
        // created for migration process
        Schema::create('student_courses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id'); //accounts.id  
            $table->integer('course_id'); //one_courses.id
            $table->date('date_joined')->nullable();
            $table->date('date_left')->nullable();
            $table->string('status')->nullable();
            $table->text('comments')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('students');
        Schema::drop('student_courses');
    }

}
