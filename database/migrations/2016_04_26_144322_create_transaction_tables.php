<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionTables extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // all treatments that are in coins
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            // admin grants coins to some user - user pays for a course/extension - user donate for another user/system
            $table->integer('type')->nullable();
            $table->integer('from_id');
            $table->integer('to_id');
            $table->integer('coins');
            $table->integer('reason_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('transactions');
    }

}
