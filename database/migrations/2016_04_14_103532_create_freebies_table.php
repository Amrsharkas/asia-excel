<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFreebiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('freebies', function (Blueprint $table) {
            /* 
             *  types : download - course - promo
             * if type is download then the path is the path of the download and course_id maybe null
             * if type is course then the course_id is a must
             * if type is promo then the course_id is a must and the path is the url of the embed video of the promo 
             */
            $table->increments('id');
            $table->string('type');
            $table->text('path');
            $table->integer('course_id')->nullable(); //elearning_course.id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('freebies');
    }

}
