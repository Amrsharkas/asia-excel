<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePenaltiesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        //this table is for teacher working fulltime, penalties for per-session work are relative to amount per session
        Schema::create('penalty_values', function (Blueprint $table) {
            $table->increments('id');
            // e.g 1: late student attendance,2: late session data, ....
            $table->integer('type');
            $table->float('value');
            $table->timestamps();
        });
        Schema::create('penalties', function (Blueprint $table) {
            $table->increments('id');
            // e.g teahcer or coordinator
            $table->integer('person_type');
            $table->integer('person_id');
            // e.g late student attendance , late session recording
            $table->integer('penalty_type');
            // NULL assignable - false approved by system - true approved by some role
            $table->boolean('approvable')->nullable();
            $table->boolean('approved')->nullable();
            $table->float('deduction_value')->nullable();
            $table->string('approvedby_type')->nullable();
            $table->integer('approvedby_id')->nullable();
            // this's useful for assignable penalties
            $table->text('description')->nullable();
            // to get all penalties of some user relating to some schedule or some session
            $table->string('schedule_type')->nullable();
            $table->integer('schedule_id')->nullable();
            $table->string('session_type')->nullable();
            $table->integer('session_id')->nullable();
            $table->timestamps();
        });
        Schema::create('penalty_matters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('penalty_id')->nullable();
            $table->string('matter_type')->nullable();
            $table->integer('matter_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('penalty_values');
        Schema::drop('penalties');
        Schema::drop('penalty_matters');
    }

}
