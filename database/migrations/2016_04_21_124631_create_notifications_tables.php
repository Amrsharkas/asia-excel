<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications',function (Blueprint $table){
            $table->increments('id');
            $table->string('notified_type');
            $table->integer('notified_id');
            $table->integer('type');
            $table->text('tags')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });
        Schema::create('notification_matters',function (Blueprint $table){
            $table->increments('id');
            $table->integer('notification_id');
            $table->string('matter_type');
            $table->integer('matter_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notifications');
        Schema::drop('notification_matters');
    }
}
