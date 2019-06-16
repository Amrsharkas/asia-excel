<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->date('date_joined')->nullable();
            $table->date('date_left')->nullable();
            $table->boolean('active')->nullable();
            $table->string('gender')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('original_country')->nullable();
            $table->string('original_country_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('skype')->nullable();
            $table->string('marital_status')->nullable();
            // student - teacher - backend
            $table->string('type')->nullable();
            $table->boolean('teacher_training')->nullable();
            $table->string('student_reg_num')->nullable();
            $table->integer('row_num')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('accounts');
    }

}
