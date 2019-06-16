<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAboutTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('about_paragraphs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('content');
            $table->integer('stuff_order');
            $table->timestamps();
        });
        Schema::create('about_tools', function(Blueprint $table) {
            $table->increments('id');
            $table->text('image_path');
            $table->text('link');
            $table->string('title');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('about_paragraphs');
        Schema::drop('about_tools');
    }

}
