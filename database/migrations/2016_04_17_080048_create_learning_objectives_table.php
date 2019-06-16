<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLearningObjectivesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('learning_objectives', function (Blueprint $table) {
            // type: elearning(module-lesson-topic) - blended - one-on-one
            // if type is elearning the reference_id is the id of elearning_content, there may be a parent_id
            // if type is blended/one-on-one the reference_id is the course_id and parent_id is null
            $table->increments('id');
            $table->string('stuff_type');
            $table->integer('stuff_id');
            $table->integer('parent_id');
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
        Schema::drop('learning_objectives');
    }

}
