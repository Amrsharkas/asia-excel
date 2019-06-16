<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeedbackesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('qm_id');
            $table->integer('teacher_id');
            //one-on-one or blended
            $table->string('type');
            $table->integer('session_id');
            $table->integer('schedule_id');
            $table->integer('course_id');
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('feedbacks');
    }

}
