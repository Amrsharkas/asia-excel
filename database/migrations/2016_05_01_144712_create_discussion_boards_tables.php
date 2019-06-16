<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDiscussionBoardsTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('schedule_id');
            $table->text('content');
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->integer('user_id');
//            $table->integer('parent_id')->nullable();
//            $table->integer('level');
            $table->text('content');
            $table->timestamps();
        });
        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('likable_type');
            $table->integer('likable_id');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('posts');
        Schema::drop('comments');
        Schema::drop('likes');
    }

}
