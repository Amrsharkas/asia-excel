s<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOneOnOneTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('one_courses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->nullable();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->text('section_image')->nullable();
            $table->text('course_image')->nullable();
            $table->text('description_overall')->nullable();
            $table->text('description_section')->nullable();
            $table->text('description_course')->nullable();
            $table->integer('max_frequency')->nullable();
            $table->float('session_duration')->nullable();
            // if max start date is 2 days .. when student enrolls in a course he must specify the 
            // start date of the course within the next 2 days
            $table->float('max_start_date')->nullable(); // in days
            $table->boolean('has_vc')->nullable(); //virtual classroom
            $table->string('arabic_proficiency')->nullable();
            $table->timestamps();
        });
        Schema::create('one_shifts', function (Blueprint $table ) {
            $table->increments('id');
            $table->integer('course_id'); //one_courses.id
            $table->integer('shift_id');
            $table->timestamps();
        });
        Schema::create('one_sessions_pricing', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable();
            $table->integer('count')->nullable(); // count of sessions
            $table->decimal('basic_basic')->nullable();
            $table->decimal('basic_premium')->nullable();
            $table->decimal('premium_basic')->nullable();
            $table->decimal('premium_premium')->nullable();
            $table->timestamps();
        });
        Schema::create('one_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //one_courses.id
            $table->string('title')->nullable();
            //paths
            $table->text('homework')->nullable();
            $table->text('material')->nullable();
            $table->timestamps();
        });
        Schema::create('one_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //one_courses.id
            $table->integer('student_id')->nullable();
            $table->integer('teacher_id')->nullable();
            $table->text('vc_link')->nullable();
            $table->integer('frequency')->nullable();
            $table->integer('shift_id')->nullable();
            $table->datetime('start_date')->nullable();
            //  sessions_count, session_price may be filled if admin/tcm added the schedule from backend
            $table->integer('n_sessions')->nullale();
            $table->decimal('n_sessions_price')->nullable();
            // if a tcm approved the task
            $table->integer('tcm_id')->nullable();
            $table->boolean('approved')->nullable();
            // the total reschedule times is calcualted from 
            // we don't calcualte sessions that have a cancel status 
            $table->integer('teacher_used_reschedule_times')->default(0);
            $table->integer('student_used_reschedule_times')->default(0);
            $table->integer('teacher_reschedule_balance')->nullable();
            $table->integer('student_reschedule_balance')->nullable();
            $table->timestamps();
        });
        // after one_schedule is approved a $one_schedule.sessions_count entries are added to this table
        // the date, from, to for each sessions are calculated from the one_shcedule and other fields are 
        // filled after the session ends
        Schema::create('one_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //one_courses.id
            $table->integer('schedule_id')->nullable(); //one_schedules.id
            $table->integer('teacher_id')->nullable();
            $table->integer('student_id')->nullable();
            $table->datetime('session_date')->nullable();
            $table->integer('shift_id')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            //homework, material are simple text
            $table->text('homework')->nullable();
            $table->text('material')->nullable();
            $table->text('attached_homework')->nullable(); //path
            $table->dateTime('attachment_date')->nullable();
            $table->integer('teacher_late_minutes')->nullable();
            $table->integer('student_late_minutes')->nullable();
            $table->string('student_attendance')->nullable();
            $table->string('teacher_attendance')->nullable();
            $table->integer('level_id')->nullable(); //one_levels.id OR we can store only the name of the level
            $table->float('homework_grade')->nullable();
            $table->time('actual_from')->nullable();
            $table->time('actual_to')->nullable();
            $table->string('lesson_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reschedulation_session')->default(false);
            $table->integer('teacher_session_id')->nullable();
            $table->integer('student_session_id')->nullable();
            $table->timestamps();
        });
        Schema::create('reschedule_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id');
            $table->integer('schedule_id');
            $table->integer('session_id');
            $table->integer('student_id');
            $table->dateTime('reschedule_date');
            $table->time('time_from');
            $table->time('time_to');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('one_courses');
        Schema::drop('one_sessions_pricing');
        Schema::drop('one_levels');
        Schema::drop('one_schedules');
        Schema::drop('one_sessions');
        Schema::drop('one_shifts');
        Schema::drop('reschedule_requests');
    }

}
