<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExtraTabsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('extra_tabs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('stuff_type');
            $table->integer('stuff_id');
            $table->string('title');
            $table->text('content'); // rich text?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('extra_tabs');
    }

}
