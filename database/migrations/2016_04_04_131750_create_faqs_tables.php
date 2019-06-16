<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFaqsTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('faq_categories', function(Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('stuff_order');
            $table->timestamps();
        });
        Schema::create('faqs', function(Blueprint $table) {
            $table->increments('id');
            $table->text('question');
            $table->text('answer');
            $table->integer('category_id');
            $table->boolean('approved');
            $table->boolean('reported');
            $table->integer('stuff_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('faq_categories');
        Schema::drop('faqs');
    }

}
