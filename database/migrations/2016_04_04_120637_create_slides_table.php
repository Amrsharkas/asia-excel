<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSlidesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('slides', function (Blueprint $table) {
            $table->increments('id');
            $table->text('caption');
            $table->text('link');
            $table->text('link_text');
            $table->text('image_path');
            $table->integer('top');
            $table->integer('left');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('slides');
    }

}
