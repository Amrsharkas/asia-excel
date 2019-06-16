<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIncomeTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('income', function(Blueprint $table) {
            $table->increments('id');
            //payment / donation 
            $table->string('type')->nullable();
            $table->integer('student_id')->nullable();
            $table->date('operation_date')->nullable();
            $table->decimal('amount')->nullable();
            $table->integer('course_id')->nullable();
            $table->text('details')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('income');
    }

}
