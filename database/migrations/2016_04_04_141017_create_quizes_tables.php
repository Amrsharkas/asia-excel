<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuizesTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('quizes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('weight');
//            // elearning quiz
//            $table->string('dependability_type');
//            $table->string('dependability_id');
            // for level access tests - the course which will be unlocked after passing this quiz
            $table->string('course_type')->nullable(); 
            $table->integer('course_id')->nullable(); 
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {

            $table->increments('id');
            $table->integer('quiz_id');
            $table->text('content'); // the question text
            // image or text or audio
            $table->string('choices_type');
            $table->integer('correct_choice_id');
            $table->integer('weight');
            $table->timestamps();
        });
        Schema::create('question_choices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('question_id');
            $table->text('content'); //text or path of image/audio
            $table->integer('stuff_order'); // if the choices orders are selected by the user and not automatically randomized by the system
        });
        Schema::create('quiz_enteries', function (Blueprint $table) {
            $table->integer('quiz_id');
            $table->integer('student_id');
            $table->boolean('finished'); // the user clicked finish or not
            $table->float('grade');
            $table->timestamps();
        });
        Schema::create('question_answers', function (Blueprint $table) {
            $table->integer('student_id');
            $table->integer('question_id');
            $table->integer('quiz_id');
            $table->integer('choice_id');
            $table->boolean('correct');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('quizes');
        Schema::drop('questions');
        Schema::drop('question_choices');
        Schema::drop('quiz_enteries');
        Schema::drop('question_answers');
    }

}
