<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('threads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user1_id');
            $table->integer('user2_id');
            $table->string('title');
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->text('subject');
            $table->text('content');
            $table->timestamps();
        });
        Schema::create('message_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('thread_id');
            $table->integer('recipient_id');
            $table->integer('message_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('threads');
        Schema::drop('messages');
        Schema::drop('message_recipients');
    }

}
