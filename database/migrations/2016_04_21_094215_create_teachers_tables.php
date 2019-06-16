<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeachersTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('teachers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('account_id')->nullable();
            $table->boolean('volunteer')->nullable();
            $table->boolean('no_arabic_communication')->nullable();
            $table->boolean('teach_children')->nullable();
            $table->string('tajweed_level')->nullable();
            $table->boolean('active')->nullable();
            //$table->integer('students_count')->nullable();
            //levels are premium OR  basic
            $table->string('arabic_level')->nullable();
            $table->string('quran_level')->nullable();
            $table->string('practices_level')->nullable();
            $table->text('education')->nullable();
            $table->string('skype_password')->nullable();
            $table->decimal('gross_due_amount')->nullable();
            $table->timestamps();
        });
        // can teach 
        Schema::create('teacher_courses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('account_id');
            $table->integer('teacher_id');
            $table->integer('course_id');
            $table->date('date_joined');
            $table->date('date_left');
            $table->boolean('active');
            $table->boolean('volunteer')->nullable();
            $table->timestamps();
        });
        Schema::create('teacher_shifts', function (Blueprint $table) {
            $table->integer('shift_id');
            $table->integer('teacher_id');
            // full-time or part-time
            $table->string('payment_type')->nullable();
            $table->float('salary')->nullable();
            $table->integer('coordinator_id')->nullable();
            $table->integer('tcm_id')->nullable();
            // there are a multiple times --> external table or multiple fields?
            $table->timestamps();
        });
        // there's an entry here for each teacher part time shift and one on one course
        Schema::create('teacher_shift_payments', function (Blueprint $table) {
            $table->integer('teacher_id');
            $table->integer('teacher_shift_id'); //teacher_shifts.id
            $table->integer('course_id');  //one_course.id
            $table->float('price')->nullable(); //payment per session for one on one
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('teachers');
        Schema::drop('teacher_courses');
        Schema::drop('teacher_shifts');
        Schema::drop('teacher_shift_payments');
    }

}
