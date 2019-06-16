<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlendedCoursesTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('blended_courses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->nullable();
            $table->string('title')->nullable();
            $table->text('section_image')->nullable();
            $table->text('course_image')->nullable();
            $table->text('description_overall')->nullable();
            $table->text('description_section')->nullable();
            $table->text('description_course')->nullable();
            $table->float('estimated_time')->nullable();
            $table->integer('max_students')->nullable();
            $table->integer('prerequisite_id')->nullable();
            $table->string('arabic_proficiency')->nullable();
            $table->timestamps();
        });
        Schema::create('blended_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //blended_courses.id
            $table->string('title')->nullable();
            $table->string('ig')->nullable(); //instructor guide
            $table->string('language')->nullable();
            $table->integer('homework_count')->nullable();
            $table->integer('teacher_reschedule_balance')->nullable(); //total
            $table->timestamps();
        });
        Schema::create('blended_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //blended_courses.id
            $table->integer('template_id')->nullable(); //blended_templates.id
            $table->integer('stuff_order')->nullable();
            $table->float('duration')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->nullable(); // or string in case it's path
            $table->text('material')->nullable();
            $table->timestamps();
        });
        Schema::create('blended_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('course_id')->nullable(); //blended_course.id
            $table->integer('template_id')->nullable(); //blended_template.id
            $table->integer('shift_id')->nullable();
            $table->integer('coordniator_id')->nullable();
            $table->text('vc_link')->nullable();
            $table->float('price')->nullable();
            $table->integer('teacher_id')->nullable();
            $table->float('teacher_payment')->nullable(); //per session
            $table->integer('teacher_remaining_reschedule_balance')->nullable();
            $table->date('locked_by')->nullable();
            $table->date('hidden_by')->nullable();
            $table->date('archived_by')->nullable();
            $table->boolean('closed_group')->nullable();
            //updated after the teacher submits the student attendance form of each form ..
            $table->float('progress')->nullable();
            // start_date and start_time should be updated if the date and time of the first session 
            // (session with stuff_order =1 ) rescheduled
            $table->datetime('first_session')->nullable(); //useful to quickly determine a schedule has started or not
            $table->boolean('full')->nullable(); //usefull to quickly determine whether the number students reached maximum or not
            // make it true when we want to archive the circle of this schedule
            $table->boolean('circle_archived')->nullable();
            $table->timestamps();
        });
        Schema::create('blended_schedule_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('session_id'); //blended_sessions.id
            $table->integer('schedule_id'); //blended_schedule.id
            $table->integer('teacher_id');
            $table->integer('shift_id')->nullable();
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->time('time')->nullable();
//            $table->text('materials')->nullable();
            //added by the teacher , hidden to the student
            $table->text('notes')->nullable();
            //actual_from, actual_to, homework are added by the teacher after the end of the session
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
//            $table->text('homework')->nullable();
            //  1 All students absent, 2 Teacher absent, 3 emergency leave, 4 connection problem
            $table->integer('cancel_status')->nullable();
            // -1 absent , 0 on time , positive number : count of minutes
            $table->integer('teacher_late_minutes')->nullable();
            // did the teacher recoreded the attendance? .. this is useful for the coordinator
            // it's updated when the teacher submits the students attendace form :)
            $table->boolean('student_attendance')->nullable();
            $table->text('recording_link')->nullable();
            $table->boolean('reschedulation_session')->default(false);
            $table->timestamps();
        });
        // students belonging to some blended schedule or students interested in some blended course
        Schema::create('blended_schedule_students', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable(); //blended_course.id
            $table->integer('template_id')->nullable();
            $table->integer('schedule_id')->nullable(); //blended_schedule.id
            // confirm clicked true and paid false means : No enough balance
            // confirm clicked true and paid true means : the user is enrolled to the course
//            $table->boolean('confirm_clicked')->nullable();
//            $table->boolean('paid')->nullable();
            $table->boolean('interested')->nullable();

            $table->timestamps();
        });
        Schema::create('blended_create_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id')->nullable();
            $table->integer('course_id')->nullable(); //blended_courses.id
            $table->integer('students_count')->nullable();
            $table->boolean('saturday')->nullable();
            $table->boolean('sunday')->nullable();
            $table->boolean('monday')->nullable();
            $table->boolean('tuesday')->nullable();
            $table->boolean('wednesday')->nullable();
            $table->boolean('thursday')->nullable();
            $table->boolean('friday')->nullable();
            // if this field is filled it means this task is accepted by some TCM
            $table->integer('tcm_id')->nullable();
            $table->timestamps();
        });
        // students involved in the create your own group request
        Schema::create('blended_create_group_students', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('group_id'); //blended_create_groups.id
            $table->integer('student_id');
            $table->timestamps();
        });
        // attendence of students in blended sessions
        Schema::create('blended_student_attendance', function (Blueprint $table) {
            $table->integer('session_id'); // blended_schedule_sessions.id
            $table->integer('student_id');
            //-1 : absent, 0: on time, positive : count of late minutes
            $table->integer('late_minutes');
            $table->timestamps();
        });
        Schema::create('blended_attached_homeworks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('session_id'); //blended_schedule_sessions.id
            $table->text('path'); //path to the attached homework
            // grade is added by the teacher
            $table->float('grade')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('blended_courses');
        Schema::drop('blended_templates');
        Schema::drop('blended_sessions');
        Schema::drop('blended_schedules');
        Schema::drop('blended_schedule_sessions');
        Schema::drop('blended_schedule_students');
        Schema::drop('blended_create_groups');
        Schema::drop('blended_create_group_students');
        Schema::drop('blended_student_attendance');
        Schema::drop('blended_attached_homeworks');
    }

}
