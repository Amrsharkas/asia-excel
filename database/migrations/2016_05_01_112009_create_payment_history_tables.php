<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentHistoryTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments',function (Blueprint $table){
            $table->increments('id');
            // tcm / teacher / coordinator
            $table->string('person_type');
            $table->integer('person_id');
            // salary - one_on_one - blended
            $table->string('payment_type')->nullable();
            // if salary
            $table->integer('shift_id')->nullable();
            // if not salary
            $table->integer('schedule_id')->nullable();
            $table->integer('session_id')->nullable();
            $table->dateTime('payment_date');
            $table->timestamps();
        });
        Schema::create('payment_matters',function (Blueprint $table){
            $table->increments('id');
            $table->integer('payment_id');
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
        Schema::drop('payments');
        Schema::drop('payment_matters');
    }
}
